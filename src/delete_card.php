<?php
require_once 'includes/db.php';

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM cards WHERE id = :id");
$stmt->execute([':id' => $id]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);
$title = 'Karte löschen';

if (!$card) {
    die("Karte nicht gefunden.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remove_quantity = (int) $_POST['remove_quantity'];

    if ($remove_quantity <= 0) {
        $error = 'Bitte eine gültige Menge eingeben.';
    } elseif ($remove_quantity >= $card['quantity']) {
        // Remove all — delete the whole row
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: index.php');
        exit;
    } else {
        // Remove only some — subtract from quantity
        $stmt = $pdo->prepare("UPDATE cards SET quantity = quantity - :remove WHERE id = :id");
        $stmt->execute([':remove' => $remove_quantity, ':id' => $id]);
        header('Location: index.php');
        exit;
    }
}
require_once 'includes/header.php';
?>


<h1>Karte entfernen</h1>

<p>
    <strong><?= htmlspecialchars($card['name']) ?></strong> — 
    <?= htmlspecialchars($card['set_name']) ?> — 
    #<?= htmlspecialchars($card['number']) ?>
</p>

<p>Aktuell auf Lager: <strong><?= (int)$card['quantity'] ?></strong></p>

<?php if (!empty($error)): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
    <label>Wie viele möchtest du entfernen?
        <input type="number" name="remove_quantity" min="1" max="<?= (int)$card['quantity'] ?>" value="1">
    </label><br><br>
    <p style="color:grey; font-size:0.9em">Wenn du alle <?= (int)$card['quantity'] ?> entfernst, wird die Karte komplett gelöscht.</p>
    <button type="submit" style="color:white; background:red; padding:8px 16px;">
        Entfernen
    </button>
    <a href="index.php">Abbrechen</a>
</form>
<?php require_once 'includes/footer.php'; ?>