<?php

  class CSVFiles {
    public $out;

    public function __construct($fileout) {
      $this->out = fopen($fileout,'a');
    }

    public function __destruct() {
      fclose($this->out);
    }

    public function ReadCSV ($file) {
      $call = array();

      if (($h = fopen($file, "r")) !== FALSE) {
	while (($row = fgetcsv($h, 1000, ",")) !== FALSE) {
	  $part = $row[3];
	  $upc = $row[11];

	  if (is_numeric($upc)) {
            $call[] = array($upc,$part);
	  }
	}
        fclose($h);
      }

      return $call;
    }

    public function WriteCSV ($list) {
      foreach ($list as $fields) {
	fputcsv($this->out,$fields);
      }
    }
  }

?>
