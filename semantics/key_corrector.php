<?php

/*
 * Класс позволяющий находить максимальное значение подобности слов 
 * Description of key_corrector
 * @author n.chudinov, не помню где брал
 */


class Corrector
{

    public $conn;

    public function __construct()
    {
        $this->conn = new mysqli("host", "login", "password", "db");
    }
    
    private function translitIt($str)
    {
        $tr = array(
                "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
                "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
                "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
                "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
                "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
                "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
                "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
                "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
                "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
                "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
                "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
                "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
                "ы"=>"yi","ь"=>"'","э"=>"e","ю"=>"yu","я"=>"ya"
            );
            return strtr($str,$tr);
    }

    public function correctWord($words)
    {
		$this->conn->query("SET NAMES utf8");
		$this->conn->query("SET CHARACTER SET utf8");
		
		$this->conn->query("SET charset utf8");
		$this->conn->query("SET character_set_client = utf8");
		$this->conn->query("SET character_set_connection = utf8");
		$this->conn->query("SET character_set_results = utf8");
		$this->conn->query("SET collation_connection = utf8_general_ci");	

		//Запрос для получения словаря
		$query = "SELECT ru_words, translit FROM word_list";

		//Получение словаря
		$word_list = array();

		if($stmt = $this->conn->prepare($query))
		{
				$stmt->execute();
				$stmt->bind_result($ru_word, $translit);
				while($stmt->fetch())
				{
					$word_translit[$ru_word] = $translit;
				}
		}

        //Перебираем массив введенных слов и записываем результаты в новый массив
        $num = 0;
        while($num < count($words))
        {
            $myWord = $words[$num];
			$num++;

            if(isset($word_list[$myWord]))
            {
                $correct[] .= $myWord;
            }
            else
            {
				$enteredWord = $this->translitIt($myWord);

		$possibleWord = NULL;

		foreach($word_translit as $n=>$k)
		{
			if(levenshtein(metaphone($enteredWord), metaphone($k)) < (mb_strlen(metaphone($enteredWord))/2)+1)
			{
				if(levenshtein($enteredWord, $k) < mb_strlen($enteredWord)/2+1)
				{
					$possibleWord[$n] = $k;
				}
			}
		}

		$similarity = 0;
		$meta_similarity = 0;
		$min_levenshtein = 1000;
		$meta_min_levenshtein = 1000;

                //Считаем минимальное расстояние Левенштейна
				
                if(count($possibleWord))
                {
					foreach($possibleWord as $n)
					{
						$min_levenshtein = min($min_levenshtein, levenshtein($n, $enteredWord));
					}

                    //Считаем максимальное значение подобности слов
                    foreach($possibleWord as $n)
                    {
                        if(levenshtein($k, $enteredWord) == $min_levenshtein)
                        {
							$similarity = max($similarity, similar_text($n, $enteredWord));
                        }
                    }

					$result = NULL;
					
                    //Проверка всего слова
                    foreach($possibleWord as $n=>$k)
                    {
                        if(levenshtein($k, $enteredWord) <= $min_levenshtein)
						{
							if(similar_text($k, $enteredWord) >= $similarity)
							{
								$result[$n] = $k;
							}
						}
					}

					foreach($result as $n)
					{
						$meta_min_levenshtein = min($meta_min_levenshtein, levenshtein(metaphone($n), metaphone($enteredWord)));
					}
					
                    //Считаем максимальное значение подобности слов
                    foreach($result as $n)
                    {
                        if(levenshtein($k, $enteredWord) == $meta_min_levenshtein)
                        {
							$meta_similarity = max($meta_similarity, similar_text(metaphone($n), metaphone($enteredWord)));
                        }
                    }
					
					$meta_result = NULL;
					
                    //Проверка через метафон
					foreach($result as $n=>$k)
					{
						if(levenshtein(metaphone($k), metaphone($enteredWord)) <= $meta_min_levenshtein)
						{
								if(similar_text(metaphone($k), metaphone($enteredWord)) >= $meta_similarity)
								{
									$meta_result[$n] = $k;
								}
						}
					}
                    
					
					$correct[] .= key($meta_result);

                }
                else
                {
                    $correct[] .= $myWord;
                }
            }
        }
        return $correct;
    }

}
?>
