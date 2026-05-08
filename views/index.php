<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: Arial; margin: 50px; text-align: center; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
        .links { margin-top: 30px; }
        .links a { display: inline-block; margin: 0 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to <?php echo $title; ?></h1>
        <p>Contract management system with digital signatures and audit trails.</p>
        <div class="links">
            <a href="/itec_contract_system/contracts">View Contracts</a>
            <a href="/itec_contract_system/migrate">Run Migrations</a>
        </div>
    </div>
</body>
</html>
