<?php
namespace Core;

class S_FNC {
    
    /**
     * Constructor y el clone ocupa ser vació
     * por efectos de control del Singleton.
     * @private
     */
    private function __construct() {
        
    }

    private function __clone() {
        
    }

    public function islogged() {
        return isset($_SESSION['account_id']) && isset($_SESSION['userid']) && isset($_SESSION['level']) ? true : false;
    }

    public function islocalhost() {
        $local = array(
            '127.0.0.1',
            '::1'
        );

        return in_array($_SERVER['REMOTE_ADDR'], $local) ? true : false;
    }

    public function isvalidurl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) ? true : false;
    }

    public function redirect($dir) {
        echo
        '
            <script>
                window.location.href = ' . $dir . ';
            </script>
        ';
    }

    /**
     * @return string Devuelve la fecha
     * http://php.net/manual/en/timezones.america.php
     */
    public function SetTime() {
        $CONFIG = new CONFIG;

        date_default_timezone_set($CONFIG->getConfig('Time_Zone'));
        $date = DateTime::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:s"));
        if ($date->format('H') >= 12) {
            $string = "PM";
        } else {
            $string = "AM";
        }
        $this->Time = $date->format('H:i') . " " . $string;
        $this->WoeDateFormat = $date;
    }

    public function GetTime() {
        if (empty($this->Time)) {
            $this->SetTime();
        }
        return $this->Time;
    }

    /**
     * @return IP Retorna el valor de IP/Proxy
     */
    public function getIP() {
        return getenv('HTTP_CLIENT_IP') ?:
                getenv('HTTP_X_FORWARDED_FOR') ?:
                getenv('HTTP_X_FORWARDED') ?:
                getenv('HTTP_FORWARDED_FOR') ?:
                getenv('HTTP_FORWARDED') ?:
                getenv('REMOTE_ADDR');
    }

    /**
     * Busca entre los Global Vars los valores a devolver
     * @param  data_cell $data_cell Campo de estructura dentro de los extends de los modulos
     * @param  Class $array     Global Var class
     */
    public function SubCDD($data_cell, $array) {
        switch ($data_cell) {
            case strstr($data_cell, "genero"): return $array->Global_Genero;
            case strstr($data_cell, "sex"): return $array->Global_Genero;
            case strstr($data_cell, "pais"): return $array->Global_pais;
            case strstr($data_cell, "class"): return $array->Global_Jobs;
            case strstr($data_cell, "mvp"): return $array->Global_MVPCard;
            case strstr($data_cell, "forum_categories"): return $array->Global_ForumCategory;
            case strstr($data_cell, "forum_group_id"): return $array->Global_ForumGroups;
            case "question": return $array->Global_questions;
            default: return null;
        }
    }

    /**
     * Devuelve el valor de un dropdown o array por el Index ejemplo array(1, "Esto Devolvería")
     */
    public function GetValueFromVarIndex($data_cell, $array, $index) {
        $arr = $this->SubCDD($data_cell, $array);
        return ( isset($arr[$index][1]) ? $arr[$index][1] : "No asignado");
    }

    //create dropdown
    public function CDD($data_cell, $row, $array, $data_value) {
        $arr = $this->SubCDD($data_cell, $array);
        if (!is_null($arr)) {
            $dropdown = '<select  class="form-control custom-select get_selectpicker_data"  name="' . ($data_cell == "mvp" ? 'opt' : $data_cell ) . "" . '">';
            foreach ($arr as $valor) {
                $dropdown .= '<option value="' . $valor[0] . '"' . ($row != "" ? (strtolower($row[$data_cell]) == strtolower($valor[0]) ? 'selected="selected"' : '') : "") . '>' . $valor[1] . '</option>';
            }
            $dropdown .= '</select>';
            return $dropdown;
        }
        return $this->C_STRING($data_cell, $row, 0, 0);
    }
    
    public function CheckMD5($texto) {
        return S_CONFIG::getConfig('UseMD5') == 1 ? md5($texto) : $texto;
    }

    public function shorter($text, $chars_limit) {
        if (strlen($text) > $chars_limit) {
            $new_text = trim(substr($text, 0, $chars_limit));
            return $new_text . "...";
        } else {
            return $text;
        }
    }
    
    private static function strposa($haystack, $needle, $offset = 0) {
        if (!is_array($needle)) {
            $needle = array($needle);
        }
        foreach ($needle as $query) {
            if (strpos($haystack, $query, $offset) !== false) {
                return true;
            }
        }
        return false;
    }    
}
