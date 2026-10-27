<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Pokemon Shop' ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<nav id="topbar">
    <button class="nav-toggle" onclick="toggleNav()">&#9776;</button>
</nav>
<nav id="sidebar">
    <a href="/index.php"><span class="nav-icon">📦</span><span class="nav-text"> Inventar</span></a>
    <a href="/add_card.php"><span class="nav-icon">➕</span><span class="nav-text"> Karte hinzufügen</span></a>
    <a href="/stats.php"><span class="nav-icon">📊</span><span class="nav-text"> Statistiken</span></a>
</nav>
<div class="container" id="main-content">