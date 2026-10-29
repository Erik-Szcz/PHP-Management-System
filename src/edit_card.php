<?php
require_once 'includes/db.php';
require_once 'includes/cardCondtion.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM cards WHERE id = :id");
$stmt->execute([':id' => $id]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$card) { header('Location: index.php'); exit; }

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $language       = $_POST['language'];
    $card_condition = $_POST['card_condition'] ?? '';
    $quantity       = (int) $_POST['quantity'];
    $buy_price      = (float) str_replace(',','.', $_POST['buy_price']);
    $margin         = (float) $_POST['margin'];
    $sell_price     = round($buy_price * (1 + $margin / 100), 2);

    if (CardCondition::tryFrom($card_condition) === null){
        $error = 'Ungültiger Zustand.';
    } elseif ($quantity < 0) {
        $error = 'Menge darf nicht negativ sein.';
    } else {
        try{
            $stmt = $pdo->prepare("
                UPDATE cards SET
                language        = :language,
                card_condition  = :card_condition,
                quantity        = :quantity,
                buy_price       = :buy_price,
                sell_price      = :sell_price,
                margin          = :margin
                WHERE id        = :id
            ");

            $stmt->execute([
                ':language'       => $language,
                ':card_condition' => $card_condition,
                ':quantity'       => $quantity,
                ':buy_price'      => $buy_price,
                ':sell_price'     => $sell_price,
                ':id'             => $id,
                ':margin'         => $margin
            ]);

            $success = 'Karte erfolgreich aktualisiert!';
            $card = $pdo->prepare("SELECT * FROM cards WHERE id = :id");
            $card->execute([':id' => $id]);
            $card = $card->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e){
            $error = 'Fehler: ' . $e->getMessage();
        }
    }
}
require_once 'includes/header.php';
?>



<h1>Karte bearbeiten</h1>
<a href="index.php">← Zurück zum Inventar</a>

<?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
        
<?php if($success): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
        
<p><strong>Karte</strong> <?= htmlspecialchars($card['name']) ?> - <?= htmlspecialchars($card['set_name']) ?></p>
<p><strong>PTCG ID:</strong><?= htmlspecialchars($card['ptcg_id']) ?></p>

<form method="POST">
    <label>Sprache:
        <select name="language">
            <option value="Deutsch" <?= $card['language'] === 'Deutsch' ? 'selected' : '' ?>>Deutsch</option>
            <option value="Englisch" <?= $card['language'] === 'Englisch' ? 'selected' : '' ?>>Englisch</option>
            <option value="Japanisch" <?= $card['language'] === 'Japanisch' ? 'selected' : '' ?>>Japanisch</option>
        </select>
    </label><br><br>

    <label>Zustand:
        <select name="card_condition">
            <?php foreach (CardCondition::cases() as $cond): ?>
                <option value="<?= htmlspecialchars($cond->value) ?>" <?= $card['card_condition'] === $cond->value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cond->value) ?>
                </option>
            <?php endforeach; ?>        
        </select>
    </label><br><br>

    <label>Menge: <input type="number" name="quantity" value="<?= (int)$card['quantity'] ?>" min="0" required></label><br><br>
    <label>Einkaufspreis (€):
        <input type="text" name="buy_price" id="buy_price"
            value="<?= number_format($card['buy_price'], 2, ',', '') ?>"
            required oninput="calculateSellPrice()">
    </label><br><br>
    <label>Marge (%):
        <input type="number" name="margin" id="margin"
            value="<?= number_format($card['margin'], 2, '.', '') ?>"
            min="0" step="1" oninput="calculateSellPrice()">
    </label><br><br>
    <label>Verkaufspreis (€):
        <input type="text" id="sell_price" name="sell_price"
            value="<?= number_format($card['sell_price'], 2, ',', '') ?>"
            readonly style="background:#162030; color:var(--color-gold);">
    </label><br><br>
    <button type="submit">Speichern</button>
</form>


<script>
    function calculateSellPrice() {
        const buyPrice  = parseFloat(document.getElementById('buy_price').value.replace(',', '.')) || 0;
        const margin    = parseFloat(document.getElementById('margin').value) || 0;
        const sellPrice = buyPrice * (1 + margin / 100);
        document.getElementById('sell_price').value = sellPrice.toFixed(2).replace('.', ',');
    }
</script>

<?php require_once 'includes/footer.php'; ?>