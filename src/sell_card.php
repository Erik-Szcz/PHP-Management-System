<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$card_id    = (int) $_POST['card_id'];
$quantity   = (int) $_POST['quantity'];
$sold_price = (float) str_replace(',', '.', $_POST['sold_price']);
$platform   = $_POST['platform'];

// Check card exists and has enough stock
$stmt = $pdo->prepare("SELECT * FROM cards WHERE id = :id");
$stmt->execute([':id' => $card_id]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$card || $quantity > $card['quantity']) {
    die("Ungültige Menge oder Karte nicht gefunden.");
}

try {
    $pdo->beginTransaction();
    // Record the sale
    $stmt = $pdo->prepare("
        INSERT INTO sales (card_id, quantity, sold_price, platform)
        VALUES (:card_id, :quantity, :sold_price, :platform)
    ");
    $stmt->execute([
        ':card_id'    => $card_id,
        ':quantity'   => $quantity,
        ':sold_price' => $sold_price,
        ':platform'   => $platform,
    ]);

    // Reduce stock — or delete if all sold
    $stmt = $pdo->prepare("UPDATE cards SET quantity = quantity - :qty WHERE id = :id");
    $stmt->execute([':qty' => $quantity, ':id' => $card_id]);

    $pdo->commit();
    header('Location: card.php?ptcg_id=' . urlencode($card['ptcg_id']));
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Fehler: " . $e->getMessage());
}