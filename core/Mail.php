<?php

namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private $mail;
    private $config;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->loadConfig();
        $this->setup();
    }

    private function loadConfig()
    {
        $this->config = [
            'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'port' => getenv('SMTP_PORT') ?: 587,
            'username' => getenv('SMTP_USERNAME') ?: '',
            'password' => getenv('SMTP_PASSWORD') ?: '',
            'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'noreply@itec.com',
            'from_name' => getenv('SMTP_FROM_NAME') ?: 'ITEC Contract System'
        ];
    }

    private function setup()
    {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->Port = $this->config['port'];
            
            // Encryption
            if ($this->config['encryption'] === 'tls') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->config['encryption'] === 'ssl') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            
            // Disable SSL verification for development (remove in production)
            $this->mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Default from address
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
        } catch (Exception $e) {
            error_log("Mail setup failed: " . $this->mail->ErrorInfo);
        }
    }

    public function send($to, $subject, $body, $isHtml = true)
    {
        // Local XAMPP/dev mode: keep workflow transitions moving when SMTP is not configured.
        if (empty($this->config['username']) || empty($this->config['password'])) {
            error_log("Mail skipped: SMTP credentials are not configured. Intended recipient: {$to}; subject: {$subject}");
            return true;
        }

        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->isHTML($isHtml);
            
            if (!$isHtml) {
                $this->mail->AltBody = strip_tags($body);
            }
            
            return $this->mail->send();
            
        } catch (Exception $e) {
            error_log("Mail send failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    public function sendWithTemplate($to, $subject, $template, $data = [])
    {
        $body = $this->renderTemplate($template, $data);
        return $this->send($to, $subject, $body, true);
    }

    private function renderTemplate($template, $data = [])
    {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/emails/{$template}.php";
        return ob_get_clean();
    }

    public function sendContractReadyForSigning($to, $contractTitle, $contractId, $clientName)
    {
        $subject = "Contract Ready for Signing - {$contractTitle}";
        $body = $this->renderTemplate('contract_ready', [
            'client_name' => $clientName,
            'contract_title' => $contractTitle,
            'contract_id' => $contractId,
            'sign_url' => $this->getSignUrl($contractId)
        ]);
        
        return $this->send($to, $subject, $body);
    }

    public function sendContractSignedByClient($to, $contractTitle, $contractId)
    {
        $subject = "Contract Signed by Client - {$contractTitle}";
        $body = $this->renderTemplate('contract_signed_by_client', [
            'contract_title' => $contractTitle,
            'contract_id' => $contractId,
            'review_url' => $this->getReviewUrl($contractId)
        ]);
        
        return $this->send($to, $subject, $body);
    }

    public function sendContractFullyExecuted($to, $contractTitle, $contractId, $clientName, $pdfPath = null)
    {
        $subject = "Contract Fully Executed - {$contractTitle}";
        $body = $this->renderTemplate('contract_executed', [
            'client_name' => $clientName,
            'contract_title' => $contractTitle,
            'contract_id' => $contractId,
            'access_url' => $this->getAccessUrl($contractId)
        ]);
        
        // Attach PDF if provided
        if ($pdfPath && file_exists($pdfPath)) {
            $this->mail->addAttachment($pdfPath);
        }
        
        return $this->send($to, $subject, $body);
    }

    private function getSignUrl($contractId)
    {
        return "http://{$_SERVER['HTTP_HOST']}/itec_contract_system/contracts/sign-digitally/{$contractId}";
    }

    private function getReviewUrl($contractId)
    {
        return "http://{$_SERVER['HTTP_HOST']}/itec_contract_system/contracts/review/{$contractId}";
    }

    private function getAccessUrl($contractId)
    {
        return "http://{$_SERVER['HTTP_HOST']}/itec_contract_system/contracts/show/{$contractId}";
    }
}
