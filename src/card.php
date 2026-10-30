<?php
require_once 'includes/db.php';
$title = 'Kartendetails';

if (empty($_GET['ptcg_id'])) {
    header('Location: index.php');
    exit;
}
$ptcg_id = $_GET['ptcg_id'];

$stmt = $pdo->prepare("SELECT * FROM cards WHERE ptcg_id = :ptcg_id ORDER BY quantity DESC");
$stmt->execute([':ptcg_id' => $ptcg_id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_quantity = array_sum(array_column($variants, 'quantity'));
$languages = array_unique(array_column($variants, 'language'));

if (empty($variants)) {
    die("Karte nicht gefunden.");
}
$card = $variants[0];

$in_stock = array_values(array_filter($variants, fn ($v) => $v['quantity'] > 0));
$sell_default = $in_stock[0] ?? null;


// Fetch card image and details from PTCG API
function fetch_tcgdex_card(string $id, array $langs): ?array
{
    foreach ($langs as $lang) {
        $ch = curl_init("https://api.tcgdex.net/v2/$lang/cards/" . urlencode($id));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => ['User-Agent: pokemon-shop/1.0'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body !== false && $code === 200) {
            $data = json_decode($body, true);
            if (!empty($data['image'])) {
                return $data;
            }
        }
    }
    return null;
}

$api_card = fetch_tcgdex_card($card['ptcg_id'], ['de', 'en']);

// Chart 1 Profit over time
$stmt = $pdo->prepare("
    SELECT DATE(s.sold_at) AS sale_date,
        ROUND(SUM((s.sold_price - c.buy_price) * s.quantity), 2) AS profit
    FROM sales s JOIN cards c ON c.id = s.card_id
    WHERE c.ptcg_id = :ptcg_id AND s.sold_at IS NOT NULL
    GROUP BY DATE(s.sold_at)
    ORDER BY sale_date ASC
");
$stmt->execute([':ptcg_id' => $ptcg_id]);
$profit_data = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Chart 2 Stock over time
$stmt = $pdo->prepare("
    SELECT c.buying_time AS event_date,
           (c.quantity + COALESCE(SUM(s.quantity), 0)) AS qty_change
    FROM cards c
    LEFT JOIN sales s ON s.card_id = c.id
    WHERE c.ptcg_id = :ptcg_id
    GROUP BY c.id, c.buying_time, c.quantity
");
$stmt->execute([':ptcg_id' => $ptcg_id]);
$purchase_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT s.sold_at AS event_date, -(s.quantity) AS qty_change
    FROM sales s JOIN cards c ON c.id = s.card_id
    WHERE c.ptcg_id = :ptcg_id AND s.sold_at IS NOT NULL
");
$stmt->execute([':ptcg_id' => $ptcg_id]);
$sale_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_events = array_merge($purchase_events, $sale_events);
usort($all_events, fn ($a, $b) => strtotime($a['event_date']) - strtotime($b['event_date']));

$daily = [];
foreach ($all_events as $ev) {
    $date = date('Y-m-d', strtotime($ev['event_date']));
    $daily[$date] = ($daily[$date] ?? 0) + $ev['qty_change'];
}
ksort($daily);

$stock_data = [];
$running = 0;
$today = date('Y-m-d');
foreach ($daily as $date => $change) {
    $running += $change;
    if ($date !== $today) {
        $stock_data[] = ['x' => $date, 'y' => max(0, $running)];
    }
}
$stock_data[] = ['x' => $today, 'y' => $total_quantity];


// Chart 3 Buy price over time
$stmt = $pdo->prepare("
    SELECT DATE(buying_time) AS buy_date,
       ROUND(AVG(buy_price), 2) AS buy_price
    FROM cards WHERE ptcg_id = :ptcg_id
    GROUP BY DATE(buying_time)
    ORDER BY buy_date ASC
");
$stmt->execute([':ptcg_id' => $ptcg_id]);
$buy_price_data = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Chart 4 Sell price over time
$stmt = $pdo->prepare("
    SELECT DATE(s.sold_at) AS sale_date,
       ROUND(AVG(s.sold_price), 2) AS sold_price
    FROM sales s JOIN cards c ON c.id = s.card_id
    WHERE c.ptcg_id = :ptcg_id AND s.sold_at IS NOT NULL
    GROUP BY DATE(s.sold_at)
    ORDER BY sale_date ASC
");
$stmt->execute([':ptcg_id' => $ptcg_id]);
$sell_price_data = $stmt->fetchAll(PDO::FETCH_ASSOC);


require_once 'includes/header.php';
?>

<div style="display:flex; gap:48px; align-items:stretch;">

    <!-- Card image -->
    <div style="display:flex; gap: 32px; align-items:flex-start;">
        <?php if ($api_card): ?>
            <img src="<?= htmlspecialchars($api_card['image'] . '/high.png') ?>" 
                 alt="<?= htmlspecialchars($card['name']) ?>"
                 style="width:280px; border-radius:12px; box-shadow: 0 4px 16px rgba(0,0,0,0.5);">
        <?php else: ?>
            <div style="width:280px; height:390px; background:#162030; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#666;">
                Kein Bild
            </div>
        <?php endif; ?>
    </div>

    <!-- Card details -->
    <div style="display:flex; flex-direction:column;">
        <h1><?= htmlspecialchars($card['name']) ?></h1>
        <p style="color:#f5a623; margin-bottom:24px;"><?= htmlspecialchars($card['set_name'] ?? '') ?></p>

        <table style="width:auto; box-shadow:none;">
            <tr><td><strong>PTCG ID</strong></td><td><?= htmlspecialchars($card['ptcg_id']) ?></td></tr>
            <tr><td><strong>Rarität</strong></td><td><?= htmlspecialchars($card['rarity'] ?? '') ?></td></tr>
            <tr><td><strong>Sprache</strong></td><td><?= htmlspecialchars(implode(', ', $languages)) ?></td></tr>
            <tr><td><strong>Menge</strong></td><td><?= (int)$total_quantity ?></td></tr>
            <tr>
                <td valign="top"><strong>Bestand</strong></td>
                <td>
                    <?php if ($in_stock): ?>
                    <table style="width:auto; box-shadow:none; margin:0;">
                        <?php foreach ($in_stock as $v): ?>
                            <tr>
                                <td style="padding:2px 12px 2px 0;"><?= htmlspecialchars($v['card_condition']) ?> · <?= htmlspecialchars($v['language']) ?></td>
                                <td style="padding:2px 12px 2px 0;"><?= (int)$v['quantity'] ?> St.</td>
                                <td style="padding: 2px 0; white-space:nowrap;">
                                    <a href="edit_card.php?id=<?= (int)$v['id'] ?>" class="btn btn-primary">Bearbeiten</a>
                                    <a href="delete_card.php?id=<?= (int)$v['id'] ?>" class="btn btn-danger">Entfernen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                        Nicht auf Lager
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Sell form -->
    <div style="display:flex; flex-direction:column;">
        <h3 style="margin-bottom: 26px;">Karte verkaufen</h3>

        <?php if ($sell_default): ?>
        <form id="sell-form" method="POST" action="sell_card.php">
            <label>Variante:
                <select name="card_id" id="sell_variant" onchange="updatePrice()">
                    <?php foreach ($in_stock as $v): ?>
                        <option value="<?=  (int)$v['id'] ?>"
                                data-price="<?= htmlspecialchars($v['sell_price']) ?>"
                                data-buy="<?= htmlspecialchars($v['buy_price']) ?>"
                                data-margin="<?= htmlspecialchars($v['margin']) ?>"
                                data-max="<?= (int)$v['quantity'] ?>">
                                <?= htmlspecialchars($v['card_condition']) ?> · <?= htmlspecialchars($v['language']) ?> (<?= (int)$v['quantity'] ?> St.)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Menge:
                <input type="number" name="quantity" id="sell_quantity" value="1" min="1" max="<?= (int)$sell_default['quantity'] ?>">
            </label>

            <label>Verkaufspreis (€):
                <input type="text" name="sold_price" id="sold_price" value="<?= number_format($sell_default['sell_price'], 2, ',', '') ?>">
            </label>

            <label>Gewinn Marge (%):
                <input type="number" id="sell_margin" value="<?= (int)$sell_default['margin'] ?>" oninput="recalcFromMargin()">
            </label>

            <label>Plattform:
                <select name="platform">
                    <option value="Website">Laden</option>
                    <option value="eBay">eBay</option>
                    <option value="Cardmarket">Cardmarket</option>
                    <option value="Privat">Privat</option>
                </select>
            </label>

            <div style="margin-top:auto; padding-top:48px;">
                <button type="submit" class="btn btn-primary">Verkauf erfassen</button>
            </div>
        </form>
        <?php else: ?>
            <p>Nicht auf Lager. </p>
        <?php endif; ?>
    </div>
</div>

<hr style="margin: 32px 0; border-color: var(--color-border);">
<h3>Statistiken</h3>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:16px;">
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:10px; padding:16px;">
        <h4 style="margin:0 0 12px; color:var(--color-gold);">Gewinn über Zeit</h4>
        <canvas id="chartProfit"></canvas>
    </div>
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:10px; padding:16px;">
        <h4 style="margin:0 0 12px; color:var(--color-gold);">Lagerbestand über Zeit</h4>
        <canvas id="chartStock"></canvas>
    </div>
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:10px; padding:16px;">
        <h4 style="margin:0 0 12px; color:var(--color-gold);">Einkaufspreis über Zeit</h4>
        <canvas id="chartBuy"></canvas>
    </div>
    <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:10px; padding:16px;">
        <h4 style="margin:0 0 12px; color:var(--color-gold);">Verkaufspreis über Zeit</h4>
        <canvas id="chartSell"></canvas>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
const gold = '#f5a623';
const gridColor = 'rgba(255,255,255,0.07)';
const tickColor = '#888';

function makeLine(id, data, label, color) {
    if (!data || data.length === 0) return;
    new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: data.map(d => formatDate(d.x)),
            datasets: [{
                label,
                data: data.map(d => d.y),
                borderColor: color,
                backgroundColor: color + '22',
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: tickColor }, grid: { color: gridColor } },
                y: { ticks: { color: tickColor }, grid: { color: gridColor } }
            }
        }
    });
}

function formatDate(str) {
    if (!str) return '';
    const d = new Date(str);
    return d.toLocaleDateString('de-DE', { day:'2-digit', month:'2-digit', year:'numeric' });
}

//Sell form
function selectedVariant() {
    return document.getElementById('sell_variant').selectedOptions[0];
}

function formatPrice(n){
    return n.toFixed(2).replace('.', ',');
}

function updatePrice() {
    const opt = selectedVariant();
    document.getElementById('sold_price').value = formatPrice(parseFloat(opt.dataset.price));
    document.getElementById('sell_margin').value = parseInt(opt.dataset.margin, 10);
    document.getElementById('sell_quantity').max = opt.dataset.max;
    document.getElementById('sell_quantity').value = 1;
}

function recalcFromMargin(){
    const buy = parseFloat(selectedVariant().dataset.buy);
    const margin = parseFloat(document.getElementById('sell_margin').value) || 0;
    document.getElementById('sold_price').value = formatPrice(buy * (1 + margin /100));
}
// PHP data → JS
const stockData   = <?= json_encode($stock_data) ?>;
const profitData = <?= json_encode(array_map(fn ($r) => ['x' => $r['sale_date'], 'y' => $r['profit']], $profit_data)) ?>;
const buyData    = <?= json_encode(array_map(fn ($r) => ['x' => $r['buy_date'],  'y' => $r['buy_price']], $buy_price_data)) ?>;
const sellData   = <?= json_encode(array_map(fn ($r) => ['x' => $r['sale_date'], 'y' => $r['sold_price']], $sell_price_data)) ?>;

makeLine('chartProfit', profitData,  'Gewinn (€)',        '#4caf50');
makeLine('chartStock',  stockData,   'Lagerbestand',      gold);
makeLine('chartBuy',    buyData,     'Einkaufspreis (€)', '#42a5f5');
makeLine('chartSell',   sellData,    'Verkaufspreis (€)', '#ef5350');
</script>

<?php require_once 'includes/footer.php'; ?>