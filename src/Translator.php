<?php

namespace Manueldinis\Smoothtranslations;

use Exception;
use InvalidArgumentException;
use PDO;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Translator implements PluginInterface
{

    // Database configurations
    private $dbConfig;
    private $pdo;
    private $lang = ""; // Language to translate to

    public function __construct(array $dbConfig)
    {
        $this->dbConfig = $dbConfig;


        try {
            $this->pdo = new PDO(
                "mysql:host={$this->dbConfig['host']};dbname={$this->dbConfig['database']}",
                $this->dbConfig['username'],
                $this->dbConfig['password']
            );
            // Check if the tables exist already
            if (!$this->tablesExist($this->pdo)) {
                $setupScript = file_get_contents(__DIR__ . '/database/setup.sql'); // get the db setup

                $res = $this->pdo->exec($setupScript);

                return $res ? ["status" => "success", "message" => "Set up finished succesful"] : ["status" => "error", "message" => "There seems to have been a problem doing the set up"];
            }
        } catch (\PDOException $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        } catch (\Throwable $th) {
            throw new Exception("There was an error during setup.");
        }
    }
    
    public function set_language(String $lang)
    {
        $this->lang = $lang;
    }

    /**
     * Translates a given text
     * 
     * @param String $text
     * @return Mixed
     */
    public function translate(String $text)
    {
        if (strlen($text) === 0) {
            throw new InvalidArgumentException("Can't Translate Empty String.");
        }
        try {
            $searchText = $text;
            $language = $this->lang;

            $sql = "SELECT * FROM st_texts
            INNER JOIN st_translations ON st_translations.text_id = st_texts.id
            INNER JOIN st_langs ON st_langs.id = st_translations.lang_id
            WHERE st_texts.text LIKE :searchText AND st_langs.language = :language";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':searchText', $searchText, PDO::PARAM_STR);
            $stmt->bindParam(':language', $language, PDO::PARAM_STR);
            $stmt->execute();

            $results = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($results["translation"]) ? $results["translation"] : "no_translation($text)";
        } catch (\PDOException $e) {
            throw new Exception("There was an error! " . $e->getMessage());
        }
    }

    /**
     * Insert language into the DB
     * 
     * @param String $langname
     * @return Mixed
     */
    public function add_language(String $langname)
    {
        try {
            // Check if langname consists only of letters and spaces
            if (!filter_var($langname, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^[a-zA-Z\s]+$/']])) {
                throw new InvalidArgumentException("Invalid language name.");
            }

            $result = $this->_insert("INSERT INTO `st_langs`(`language`) VALUES (:langname)")
                ->execute(["langname" => $langname]);

            return $result ? ["status" => "success", "message" => "Language inserted successfully"]
                : ["status" => "error", "message" => "Error inserting the language"];
        } catch (\PDOException $th) {
            throw new Exception("An error has occured! " . $th->getMessage());
        }
    }

    /**
     * Insert text into the DB
     * 
     * @param String $text
     * @return Mixed
     */
    public function add_text(String $text)
    {
        try {
            $result =  $this->_insert("INSERT INTO `st_texts`(`text`) VALUES (:text)")
                ->execute(["text" => $text]);

            return $result ? ["status" => "success", "message" => "Text inserted successfully"]
                : ["status" => "error", "message" => "Error inserting the text"];
        } catch (\PDOException $th) {
            throw new Exception("An error has occured! " . $th->getMessage());
        }
    }

    /**
     * Insert translations into the DB
     * 
     * @param String $translation
     * @param Int $language_id
     * @param Int $text_id
     * @return Mixed
     */
    public function add_translation(String $translation, Int $language_id, Int $text_id)
    {
        try {
            $result = $this->_insert("INSERT INTO `st_translations`(`lang_id`, `text_id`, `translation`)
            VALUES (:lang, :text, :translation)")
                ->execute(["lang" => $language_id, "text" => $text_id, "translation" => $translation]);

            return $result ? ["status" => "success", "message" => "Translation inserted successfully"]
                : ["status" => "error", "message" => "Error inserting the translation"];
        } catch (\PDOException $th) {
            throw new Exception("An error has occured! " . $th->getMessage());
        }
    }

    /**
     * Gets all inserted languages
     * 
     * @return JSON
     */
    public function get_languages(Int $id = null, Bool $json = false)
    {
        $languages = [];
        foreach ($this->_get("SELECT * FROM st_langs" . (isset($id) ? "WHERE id = " . $id : "")) as $key => $langs) {
            $languages[] = [
                "id" => $langs['id'],
                "language" => $langs['language'],
            ];
        }
        return $json ? json_encode($languages) : $languages;
    }

    /**
     * Gets all inserted Texts
     * 
     * @return JSON
     */
    public function get_texts(Int $id = null, Bool $json = false)
    {
        foreach ($this->_get("SELECT * FROM st_texts " . (isset($id) ? "WHERE id = " . $id : "")) as $key => $text) {
            $texts[] = [
                "id" => $text['id'],
                "text" => $text['text'],
            ];
        }
        return $json ? json_encode($texts) : $texts;
    }

    /**
     * Gets all inserted Translations
     * 
     * @return JSON
     */
    public function get_translations(Int $id = null, Bool $json = false)
    {
        $query = "SELECT * FROM st_translations 
        inner join st_texts on st_translations.text_id = st_texts.id
        inner join st_langs on st_langs.id = st_translations.lang_id
        " . (isset($id) ? "WHERE st_translations.id = " . $id : "");

        foreach ($this->_get($query) as $key => $translation) {
            $translations[] = [
                "id" => $translation['id'],
                "lang" => $translation['language'],
                "text" => $translation['text'],
                "translation" => $translation['translation'],
            ];
        }
        return $json ? json_encode($translations) : $translations;
    }

    // Check if table already exists
    private function tablesExist()
    {
        $tableName = 'st_texts';

        $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false;
    }

    // Queries
    private function _get(String $query)
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    // Do the start of inserting with PDO
    private function _insert(String $query)
    {
        return $this->pdo->prepare($query);
    }

    public function activate(Composer $composer, IOInterface $io){}

    public function deactivate(Composer $composer, IOInterface $io){}

    public function uninstall(Composer $composer, IOInterface $io) {}
}
