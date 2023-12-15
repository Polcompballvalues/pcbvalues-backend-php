<?php

/**
 * Fetches and parses a JSON from a given URL
 * @param string $url URL of the source of the JSON data
 * @return array Associative array version of the JSON data
 */
function fetch_json(string $url): array
{
    $options = [
        'http' => [
            'header' => 'User-Agent: Mozilla/5.0 PHP',
            'method' => 'GET'
        ]
    ];

    $ctx  = stream_context_create($options);

    $result = file_get_contents($url, false, $ctx);

    if (!$result) {
        throw new Exception('No response body');
    }

    $data = json_decode($result, true);

    if ($data === NULL) {
        throw new Exception('Unable to decode JSON data');
    }

    return $data;
}

/**
 * Class containing URL constants with the necessary endpoints
 */
class GITHUB
{
    /**URL to the repository's raw package.json */
    const PACKAGE = 'https://github.com/pcbvalues/pcbvalues.github.io/raw/main/package.json';
    /**URL to the repository's raw users.json */
    const USERLIST = 'https://github.com/pcbvalues/pcbvalues.github.io/raw/main/dist/users.json';
    /**URL to the commits API, retrieving only the last commit */
    const API = 'https://api.github.com/repos/pcbvalues/pcbvalues.github.io/commits?per_page=1';
}


/**
 * Fetches and formats the information relating to the latest commit
 * @return string Formatted string containing the last commit time in UTC and the author
 */
function last_commit(): string
{
    $commit = fetch_json(GITHUB::API);

    $commit_date = $commit[0]['commit']['author']['date'];

    $author = $commit[0]['commit']['author']['name'];

    $dt = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $commit_date);

    $author_link = '<a href=https://github.com/' . urlencode($author) . ">$author</a>";

    return $dt->format('d/M/Y @ H:i') . ' (UTC) by ' . $author_link;
}

/**
 * Fetches version of the package by parsing the package.json file
 * @return string Package version
 */
function pkg_version(): string
{
    $pkg = fetch_json(GITHUB::PACKAGE);

    $version = $pkg['version'];

    if (!$version) {
        throw new Exception('Version key missing');
    }

    return $version;
}

/**
 * Generates an HTML link element to a given score in the 
 * pcbvalues.github.io gallery
 * @param string $user Name of the user to link to.
 * @return string HTML link element containing the score
 */
function score_link(string $user): string
{
    return '<a href="https://pcbvalues.github.io/gallery.html?user=' .
        urlencode($user) . '">' . htmlentities($user) . '</a>';
}

/**
 * Generates HTML table from header array and 2-dimmensional rows array
 * @param array $headers 
 * @param array<array-key,array> $rows
 * @return string Generated HTML table
 * @throws Exception If invalid arrays provided
 */
function generate_table(array $headers, array $rows): string
{
    if (gettype($rows[0]) !== 'array') {
        throw new Exception('Invalid rows 2D array provided');
    }

    $h_count = count($headers);

    $tagged_headers = array_map(
        fn ($val, $index): string => ($index > 0 ? '<th>' : '<th class="sorted">') .
            htmlspecialchars($val) . '</th>',
        $headers,
        array_keys($headers)
    );

    $heading = '<thead><tr>' . implode('', $tagged_headers) . '</tr></thead>';

    $tagged_rows = [];

    foreach ($rows as $row) {
        if (count($row) !== $h_count) {
            throw new Exception('Invalid row size');
        }

        $t_row = array_map(
            fn ($val): string => '<td>' . $val . '</td>',
            $row
        );

        $tagged_rows[] = '<tr>' . implode('', $t_row) . '</tr>';
    }

    $body = '<tbody>' . implode('', $tagged_rows) . '</tbody>';

    return '<table>' . $heading . $body . '</table>';
}
