<?php
$title = 'Create New Contract';

require_once __DIR__ . "/../../config/constants.php";

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <script>
                window.BASE_URL = '<?= BASE_URL ?>';
            </script>

            <?php
            $editor_config = [
                'contract_id' => 0,
                'content' => '',
                'is_locked' => false,
                'readonly' => false,
                'height' => '600px',
                'show_version_history' => false,
                'show_tracked_changes' => true
            ];

            include __DIR__ . '/../components/contract-editor.php';
            ?>

        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {

    Alpine.data('newContractEditor', () => ({
        content: '',

        init() {
            const editor = document.querySelector('[contenteditable="true"]');

            if (editor) {
                this.content = editor.innerHTML;

                editor.addEventListener('input', () => {
                    this.content = editor.innerHTML;
                });
            }
        },

        saveDocument() {

            const editor = document.querySelector('[contenteditable="true"]');

            if (editor) {
                this.content = editor.innerHTML;
            }

            fetch(`${window.BASE_URL}/api/contracts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    title: 'Untitled Contract',
                    content: this.content
                })
            })
            .then(response => response.json())
            .then(data => {

                if (data.success) {
                    window.location.href =
                        `${window.BASE_URL}/contracts/${data.contract_id}/edit`;
                } else {
                    alert(data.message || 'Failed to create contract');
                }

            })
            .catch(error => {
                console.error(error);
                alert('Something went wrong');
            });
        }
    }));

});
</script>



<?php
$content = ob_get_clean();

require_once __DIR__ . '/../layouts/app.php';
?>