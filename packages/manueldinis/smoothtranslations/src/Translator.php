<?php

namespace Manueldinis\Smoothtranslations;

use Exception;
use PDO;
use Ramsey\Uuid\Type\Integer;

class Translator
{

    // Database configurations
    private $dbConfig;
    private $pdo;
    private $lang; // Language to translate to

    public function __construct(array $dbConfig, String $lang)
    {
        $this->dbConfig = $dbConfig;
        $this->lang = $lang;

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

    /**
     * Translates a given text
     * 
     * @param String $text
     * @return Mixed
     */
    public function translate(String $text)
    {
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
            return isset($results["translation"])?$results["translation"]:"no_translation($text)";
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

            $sql = "INSERT INTO `st_langs`(`language`) VALUES (:langname)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(["langname" => $langname]);

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

            $sql = "INSERT INTO `st_texts`(`text`) VALUES (:text)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(["text" => $text]);

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

            $sql = "INSERT INTO `st_translations`(`lang_id`, `text_id`, `translation`)
                    VALUES (:lang, :text, :translation)";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(["lang"=>$language_id, "text" => $text_id, "translation"=>$translation]);

            return $result ? ["status" => "success", "message" => "Translation inserted successfully"]
                : ["status" => "error", "message" => "Error inserting the translation"];
        } catch (\PDOException $th) {
            throw new Exception("An error has occured! " . $th->getMessage());
        }
    }

    // TODO: Get methods for tables

    // Check if table already exists
    private function tablesExist()
    {
        $tableName = 'st_texts';

        $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false;
    }
}
