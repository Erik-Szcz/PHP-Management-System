<?php
require_once 'includes/db.php';

$id = (int) ($_GET['id'] ?? 0);

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
    } else {
        $remove = min($remove_quantity, (int) $card['quantity']);
        $stmt = $pdo->prepare("UPDATE cards SET quantity = quantity - :remove WHERE id = :id");
        $stmt->execute([':remove' => $remove, ':id' => $id]);
        header('Location: card.php?ptcg_id=' . urlencode($card['ptcg_id']));
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
    <p style="color:grey; font-size:0.9em">Wenn du alle <?= (int)$card['quantity'] ?> entfernst, verschwindet die Karte aus dem Inventar. Die Verkaufshistorie bleibt erhalten.</p>
    <button type="submit" style="color:white; background:red; padding:8px 16px;">
        Entfernen
    </button>
    <a href="card.php?ptcg_id=<?= urlencode($card['ptcg_id']) ?>">Abbrechen</a>
</form>
<?php require_once 'includes/footer.php'; ?>