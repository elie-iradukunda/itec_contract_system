<?php

namespace Services;

class OnlyOfficeService
{
    public function config($id, array $contract, $baseUrl)
    {
        $server = rtrim(getenv('ONLYOFFICE_DOCUMENT_SERVER') ?: '', '/');
        $baseUrl = rtrim(getenv('APP_URL') ?: $baseUrl, '/');
        $path = $this->ensureDocument($id);
        $state = strtoupper($contract['signing_state'] ?? 'DRAFT');
        $mtime = filemtime($path);
        $config = ['documentType' => 'word', 'width' => '100%', 'height' => '900px',
            'document' => ['fileType' => 'docx', 'key' => "contract-{$id}-{$mtime}", 'title' => $contract['file_name'] ?? 'contract.docx',
                'url' => "{$baseUrl}/contracts/{$id}/download?v={$mtime}", 'permissions' => ['edit' => $state === 'DRAFT', 'download' => true]],
            'editorConfig' => ['mode' => $state === 'DRAFT' ? 'edit' : 'view', 'callbackUrl' => "{$baseUrl}/contracts/{$id}/onlyoffice/callback",
                'lang' => 'en', 'user' => ['id' => 'staff', 'name' => 'Staff Portal'], 'customization' => ['zoom' => 100]]];
        if ($token = $this->jwt($config)) $config['token'] = $token;
        $apiUrl = $server ? "{$server}/web-apps/apps/api/documents/api.js" : '';
        return ['enabled' => $server !== '', 'available' => $apiUrl && $this->isAvailable($apiUrl),
            'apiUrl' => $apiUrl, 'serverUrl' => $server, 'setupHint' => 'Start ONLYOFFICE Document Server on port 8082, then refresh.',
            'forceSaveUrl' => "/itec_contract_system/contracts/{$id}/onlyoffice/force-save", 'config' => $config];
    }
    public function download($id)
    {
        $path = $this->ensureDocument($id);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: inline; filename="contract.docx"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
    public function callback($id, array $payload)
    {
        $status = (int) ($payload['status'] ?? 0);
        if (!in_array($status, [2, 6], true)) return ['error' => 0];
        if (empty($payload['url'])) return ['error' => 1];
        $data = $this->fetch($payload['url']);
        if ($data === false) return ['error' => 1];
        $path = $this->documentPath($id);
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        file_put_contents($path, $data);
        return ['error' => 0];
    }
    public function forceSave($id, $key)
    {
        $server = rtrim(getenv('ONLYOFFICE_DOCUMENT_SERVER') ?: '', '/');
        if ($server === '' || $key === '') return ['success' => false, 'message' => 'ONLYOFFICE is not configured'];
        $body = ['c' => 'forcesave', 'key' => $key];
        $payload = ($token = $this->jwt($body)) ? ['token' => $token] : $body;
        $result = $this->postJson("{$server}/command?shardkey=" . rawurlencode($key), $payload);
        if ($result === []) $result = $this->postJson("{$server}/coauthoring/CommandService.ashx", $payload);
        $ok = isset($result['error']) && (int) $result['error'] === 0;
        return ['success' => $ok, 'message' => $ok ? 'Save requested in ONLYOFFICE' : 'ONLYOFFICE force save failed'];
    }
    private function ensureDocument($id)
    {
        $path = $this->documentPath($id);
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        if (!file_exists($path) || file_get_contents($path, false, null, 0, 2) !== 'PK') {
            copy(__DIR__ . '/../storage/templates/blank-contract.docx', $path);
        }
        return $path;
    }
    private function documentPath($id) { return __DIR__ . '/../storage/contracts/' . (int) $id . '/contract.docx'; }
    private function fetch($url)
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true]);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }
    private function postJson($url, array $body)
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($body)]);
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data ?: '{}', true) ?: [];
    }
    private function isAvailable($url)
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
        curl_exec($curl); $code = curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        return $code >= 200 && $code < 400;
    }
    private function jwt(array $payload)
    {
        $secret = getenv('ONLYOFFICE_JWT_SECRET') ?: '';
        if ($secret === '') return '';
        $header = $this->b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $this->b64(json_encode($payload));
        return $header . '.' . $body . '.' . $this->b64(hash_hmac('sha256', "$header.$body", $secret, true));
    }
    private function b64($value) { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
}
