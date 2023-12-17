<?php
include '../lib/auxiliary.php';

$users = fetch_json(GITHUB::USERLIST);

$flat_users = array_map(
    fn ($val) => [score_link($val[0]), ...$val[1]],
    $users
);

$headers = [
    'Name',
    'Demeanor',
    'Personality',
    'Judgement',
    'Politics',
    'Realism',
    'Perception',
    'Hornyposting'
];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <title>PCBValues scores backend</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        <?php include '../lib/style.min.css'; ?>
    </style>
</head>

<body>
    <?php include '../lib/github-corner.html'; ?>
    <h1>PCBValues scores backend</h1>
    <div id="pkg-version">
        PCBValues latest version is: <?php echo pkg_version(); ?>
    </div>
    <div id="last-commit">
        Latest commit was at <?php echo last_commit(); ?>
    </div>
    <div>
        <button id="check-wbhook">Check Discord status</button>
        <div id="status-display"></div>
    </div>
    <div>
        <button class="collapse-button">Expand scores</button>
        <div class="scrollable collapsible collapsed">
            <?php echo generate_table($headers, $flat_users); ?>
        </div>
    </div>
    <script type="module">
        <?php include '../lib/script.min.js'; ?>
    </script>
</body>

</html>