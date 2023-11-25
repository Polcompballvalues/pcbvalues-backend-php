<?php

/**
 * Class representing, parsing, validating and exporting a set of scores.
 */
class Scores
{
    /** Userame of the submitter */
    public ?string $name;
    /** Scores of the submitter */
    private ?array $scores;
    /** Timestamp the test scores were generated at */
    private ?string $timestamp;
    /** Short or Full edition */
    private ?string $edition;
    /** SHA-512 digest of the scores to detect tampering */
    private ?string $hash;
    /** Number of times taken by the user */
    private ?int $takes;
    /** Version of the test taken */
    private ?string $version;
    /** User agent of the submitting browser */
    private ?string $user_agent;
    /** Preferred date formatting */
    const DT_FMT = 'H:i:s - d/m/Y';

    /**
     * Constructs new instance of Scores class based on the parsed data
     * @param array $data_obj Associative array version of the submitted data
     * @param array $user_agent Nullable string representing the request's user agent
     */
    function __construct(array $data_obj, ?string $user_agent)
    {
        $this->name = $data_obj['name'];
        $this->scores = $data_obj['vals'];
        $this->timestamp = $data_obj['time'];
        $this->edition = $data_obj['edition'];
        $this->hash = $data_obj['digest'];
        $this->takes = $data_obj['takes'];
        $this->version = $data_obj['version'];

        $this->user_agent = $user_agent;
    }

    /**
     * Digests a string and returns a base64 encoded representation of the 
     * raw binary data digested by SHA-512
     * @param string $in_str String to encode
     * @return string Base64 encoded digest
     */
    private static function digest_string(string $in_str): string
    {
        $raw_bytes = hash('sha512', $in_str, true);
        return base64_encode($raw_bytes);
    }

    /**
     * Formats all scores as strings with 1 decimal point and joins them
     * @return string String with the joined formatted scores
     */
    private function join_scores(): string
    {
        $str_array = [];

        foreach ($this->scores as $score) {
            $str_array[] = number_format($score, 1);
        }

        return implode(',', $str_array);
    }

    /**
     * Returns a string representing the authenticity of the scores
     * @return string Authenticity label
     */
    private function scores_match(): string
    {
        if (!$this->hash) {
            return "\xe2\x9d\x93 Missing score authentication";
        }

        $gen_hash = $this->digest_string($this->join_scores());

        if ($gen_hash === $this->hash) {
            return "\xe2\x9c\x85 Authentic score";
        }

        return "\xe2\x9d\x8c Tampered score";
    }

    /**
     * Returns a string representation of the edition of the test taken
     * @return string Edition label
     */
    private function get_edition(): string
    {
        if (!$this->edition) {
            return "\xe2\x9d\x94 Missing Edition";
        }

        return match (strtolower(trim($this->edition))[0]) {
            's' => "\xf0\x9f\xa4\x8f Short Edition",
            'f' => "\xf0\x9f\x90\x8d Full Edition",
            default => "\xe2\x9d\x94 Unknown Edition"
        };
    }

    /**
     * Parses ISO timestamp and returns string in preferred datetime format
     * or appropriate label to signify a missing or broken timestamp
     * @param string $input Nullable ISO 8601 formatted string representing a timestamp
     * @return string Formatted datetime or error label
     */
    private static function parse_ts(?string $input): string
    {
        if (!$input) {
            return "\xe2\x8f\xb3 Missing time";
        }

        try {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $input);
            if ($dt) {
                return $dt->format(Scores::DT_FMT);
            }
        } catch (Exception $e) {
            //Ignore and return default value
        }

        return "\xe2\x8f\xb3 Broken timestamp";
    }

    /**
     * Sanitizes string by escaping markdown syntax characters
     * @param string $input Nullabel unsanitized input
     * @return string Sanitized output or missing label
     */
    private static function md_sanitize(?string $input): string
    {
        if (!$input) {
            return 'Missing';
        }

        return preg_replace('/([_`\*\[\]\(\)])/', '\\\\$1', $input);
    }

    /**
     * Checks the validity of the scores in the class
     * @param int $count The number of keys in the scores
     * @throws Exception If score is invalid
     */
    private function validate_scores(int $count)
    {
        if (count($this->scores) !== $count) {
            throw new Exception('Wrong score count');
        }

        foreach ($this->scores as $score) {
            if (!match (gettype($score)) {
                'integer', 'double', 'float' => true,
                default => false,
            }) {
                throw new Exception('Score is not numeric value');
            }

            if ($score < 0 || $score > 100) {
                throw new Exception('Score is outside valid range');
            }
        }
    }

    /**
     * Checks the validity of the name and scores in the class
     * @param int $count The number of keys in the scores
     * @throws Exception If name or score is invalid
     */
    public function valid(int $count)
    {
        if (strlen($this->name) > 100) {
            throw new Exception('Name is too large');
        }

        $this->validate_scores($count);
    }

    /**
     * Exports the submitted scores for a formatted markdown message
     * (Discord flabour), ready to be sent in a webhook's body
     * @return string Formatted markdown representation of the scores
     */
    public function to_code(): string
    {
        $data = [
            'name' => $this->name,
            'values' => $this->scores
        ];

        $username = $this->md_sanitize($this->name);
        $authenticity = $this->scores_match();
        $edition = $this->get_edition();
        $takes = $this->takes ?? "\xe2\x81\x89\xef\xb8\x8f Missing takes";
        $user_agent = $this->md_sanitize($this->user_agent);
        $version = $this->md_sanitize($this->version);

        $now = new DateTime();
        $sub_timestamp = $now->format(Scores::DT_FMT);
        $ans_timestamp = $this->parse_ts($this->timestamp);

        return "**User:** $username" . PHP_EOL .
            "**Time Submitted:** $sub_timestamp (UTC)" . PHP_EOL .
            "**Time Answered:** $ans_timestamp (UTC)" . PHP_EOL .
            "**Edition:** $edition" . PHP_EOL .
            "**Authenticity:** $authenticity" . PHP_EOL .
            "**Takes**: $takes" . PHP_EOL .
            "**User Agent:** $user_agent" . PHP_EOL .
            "**Version:** $version" . PHP_EOL .
            '```json' . PHP_EOL .
            json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL .
            '```';
    }
}
