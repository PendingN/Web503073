<header class="dashboard-toolbar">
    <form class="dashboard-search" method="get" action="collection.php" role="search">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <label class="sr-only" for="dashboard-search">Tìm tên cây</label>
        <input id="dashboard-search" name="search" type="search" placeholder="Tìm tên cây..." value="<?= e(request_string($_GET, 'search')) ?>">
        <button type="submit">Tìm</button>
    </form>
</header>

