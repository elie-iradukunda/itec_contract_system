<footer class="site-footer">
    <div>
        <strong>ItecPay Contract Portal</strong>
        <span>Draft, execute, verify, and distribute contracts in one place.</span>
    </div>
    <small>Finance System workspace</small>
</footer>

<?php foreach (($pageScripts ?? []) as $script): ?>
    <script src="<?= $script ?>"></script>
<?php endforeach; ?>
</body>
</html>
