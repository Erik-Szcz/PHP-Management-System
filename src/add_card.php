<?php
require_once 'includes/db.php';

$error = '';
$success = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $ptcg_id = trim($_POST['ptcg_id']);
    $name = trim($_POST['name']);
    $set_id = trim($_POST['set_id']);
    $set_name = trim($_POST['set_name']);
    $number = trim($_POST['number']);
    $rarity = trim($_POST['rarity']);
    $language = trim($_POST['language']);
    $card_condition = trim($_POST['card_condition']);
    $quantity = trim($_POST['quantity']);
    $buy_price = (float) str_replace(',', '.', trim($_POST['buy_price']));
    $margin     = (float) $_POST['margin'];
    $sell_price = round($buy_price * (1 + $margin / 100), 2);

    if (empty($ptcg_id) || empty($name) || empty($set_id) || empty($card_condition)) {
        $error = 'Bitte alle Pflichtfelder ausfüllen.';
    } else {
        try{
            $stmt = $pdo->prepare("INSERT INTO cards(ptcg_id, name, set_id, set_name, number, rarity, language, card_condition, quantity, buy_price, sell_price, margin)
                VALUES 
                    (:ptcg_id, :name, :set_id, :set_name, :number, :rarity, :language, :card_condition, :quantity, :buy_price, :sell_price, :margin)
            ");

            $stmt->execute([
                ':ptcg_id'         => $ptcg_id,
                ':name'            => $name,
                ':set_id'          => $set_id,
                ':set_name'        => $set_name,
                ':number'          => $number,
                ':rarity'          => $rarity,
                ':language'        => $language,
                ':card_condition'  => $card_condition,
                ':quantity'        => $quantity,
                ':buy_price'       => $buy_price,
                ':sell_price'      => $sell_price,
                ':margin'          => $margin,
            ]);
            $success = 'Karte erfolgreich hinzugefügt';
        }
        catch(PDOException $e){
            $error = 'Fehler: ' . $e->getMessage();
        }
    }    
}

$title = 'Karte hinzufügen';
require_once 'includes/header.php';
?>
    <?php if($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>    


<h2>Karte suchen</h2>
<br>
<input type="text" id="search" placeholder="Pokemonname eingeben..." oninput="debounceSearch()">
<ul id="suggestions" style="list-style:none; padding:0; margin:0; max-width:600px; background:var(--color-surface); border:1px solid var(--color-border); border-radius:6px; display:none;"></ul>
<br><br>
<select id="card_select" style="display:none; width:100%; max-width:600px" onchange="fillForm()">
    <option value="">-- Karte auswählen --</option>
</select>

<div id="card-preview" style="display:none; margin:16px 0; padding:12px; background:var(--color-surface); border:1px solid var(--color-gold); border-radius:8px;">
    <strong style="color:var(--color-gold);" id="preview-name"></strong><br>
    <small id="preview-details" style="color:#aaa;"></small>
</div>

<form method="POST" id="card-form" style="display:none;">
    <input type="hidden" name="ptcg_id" id="ptcg_id">
    <input type="hidden" name="name" id="name">
    <input type="hidden" name="set_id" id="set_id">
    <input type="hidden" name="set_name" id="set_name">
    <input type="hidden" name="number" id="number">
    <select name="rarity" id="rarity" style="display:none;"></select>
    <label>Sprache:
        <select name="language">
            <option value="Deutsch">Deutsch</option>
            <option value="Englisch">Englisch</option>
            <option value="Japanisch">Japanisch</option>
        </select>
    </label>
    <label>Zustand:
        <select name="card_condition" required>
            <option value="Mint">Mint</option>
            <option value="Near Mint">Near Mint</option>
            <option value="Excellent">Excellent</option>
            <option value="Good">Good</option>
            <option value="Light Played">Played</option>
            <option value="Played">Played</option>
            <option value="Poor">Poor</option>
        </select>
    </label><br><br>
    <label>Menge: <input type="number" name="quantity" value="1" min="0" required></label><br><br>
    <label>Einkaufspreis (€): <input type="text" name="buy_price" id="buy_price" placeholder="0,00" required></label><br><br>
    <label>Marge (%): <input type="number" name="margin" id="margin" value="20" min="0" step="1"></label><br><br>
    <button type="submit">Karte speichern</button>
</form>


<script>
let debounceTimer;
function debounceSearch() {
    clearTimeout(debounceTimer);
    const input = document.getElementById('search').value.trim();
    const list = document.getElementById('suggestions');
    if (input.length < 3) { list.style.display = 'none'; return; }
    debounceTimer = setTimeout(() => liveSearch(input), 4);
}

async function liveSearch(name) {
    const list = document.getElementById('suggestions');
    try {
        const response = await fetch(`search_cards.php?name=${encodeURIComponent(name)}`);
        const data = await response.json();
        cachedCards = data.data || [];

        // Extract unique names
        const uniqueNames = [...new Set(cachedCards.map(c => c.name))].slice(0, 8);

        if (uniqueNames.length === 0) { list.style.display = 'none'; return; }

        list.innerHTML = uniqueNames.map(n =>
            `<li onclick="selectName('${n}')"
                style="padding:10px 14px; cursor:pointer; border-bottom:1px solid var(--color-border);"
                onmouseover="this.style.background='var(--color-hover)'"
                onmouseout="this.style.background=''">
                ${n}
            </li>`
        ).join('');
        list.style.display = 'block';
    } catch(e) {
        console.error('Suchfehler:', e);
    }
}

function selectName(name) {
    document.getElementById('search').value = name;
    document.getElementById('suggestions').style.display = 'none';

    const select = document.getElementById('card_select');
    select.innerHTML = '<option value="">-- Karte auswählen --</option>';
    const matches = cachedCards.filter(c => c.name === name);
    matches.forEach(card => {
        const option = document.createElement('option');
        option.value = JSON.stringify(card);
        option.textContent = `${card.name} - ${card.set?.id ?? card.id.split('-')[0]} - ${card.localId} - ${card.rarity ?? 'N/A'}`;
        select.appendChild(option);
    });
    select.style.display = 'block';
}

async function fillForm() {
    const select = document.getElementById('card_select');
    if (!select.value) return;

    const brief = JSON.parse(select.value);

    const res = await fetch(`https://api.tcgdex.net/v2/de/cards/${encodeURIComponent(brief.id)}`);
    const card = await res.json();

    document.getElementById('ptcg_id').value  = card.id;
    document.getElementById('name').value     = card.name;
    document.getElementById('set_id').value   = card.set.id;
    document.getElementById('set_name').value = card.set.name;  // now correct
    document.getElementById('number').value   = card.localId;   // TCGdex uses localId not number

    // rarity
    const raritySelect = document.getElementById('rarity');
    raritySelect.innerHTML = '';
    const r = card.rarity ?? 'N/A';
    const opt = document.createElement('option');
    opt.value = r; opt.textContent = r;
    raritySelect.appendChild(opt);

    document.getElementById('preview-name').textContent = card.name;
    document.getElementById('preview-details').textContent =
        `${card.set.id} · ${card.localId} · ${r} · ID: ${card.id}`;
    document.getElementById('card-preview').style.display = 'block';
    document.getElementById('card-form').style.display = 'block';

    calculateSellPrice();
}

function calculateSellPrice() {
    const buyPrice  = parseFloat(document.getElementById('buy_price').value.replace(',', '.')) || 0;
    const margin    = parseFloat(document.getElementById('margin').value) || 0;
    const sellPrice = buyPrice * (1 + margin / 100);
}
</script>

<?php require_once 'includes/footer.php'; ?>