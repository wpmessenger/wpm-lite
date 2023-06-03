<?php
header('Content-Type: text/xml');

$other_number = isset($_GET['OtherNumber']) ? $_GET['OtherNumber'] : '';

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Dial>
        <Number><?php echo htmlspecialchars($other_number, ENT_XML1, 'UTF-8'); ?></Number>
    </Dial>
</Response>
