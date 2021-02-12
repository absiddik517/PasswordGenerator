<?php

    namespace App;
    
    class Controller
    {
        public $file_name = null;
        public $v_name = null;
        public $phone = null;
        public $bod = null;
        public $keyword = null;
        
        // array coversion
        public $names = [];
        public $phones = [];
        public $phns = [];
        public $keywords = [];
        private $dobs = []; // [ dd, mm, yyyy]
        
        // settings
        private $minLen = 0;
        private $maxLen = 0;
        private $symbols = ['.'];
        public $path = '';
        public $line = '<br/>';
        
        public $passwords = [];
        
        public $valid_passwords = [];
        
        public function __construct($request)
        {
            $this->setVariables($request);
            $this->setArray();
            $this->run();
        }
        
        private function setVariables($request){
            $this->file_name = $request['file_name'];
            $this->v_name = $request['v_name'];
            $this->dob = $request['dob'];
            $this->keyword = $request['keywords'];
            $this->phone = $request['phones'];
            
            include('Config.php');
            $this->minLen = $config['min_length'];
            $this->maxLen = $config['max_length'];
            $this->path = $config['path'];
            $this->symbols = $config['symbols'];
        }
        
        private function setArray(){
            if(str_word_count($this->v_name) > 1){
                $this->names = explode(' ', $this->v_name);
            }else{
                $this->names = [$this->v_name];
            }
            
            $this->phones = explode(' ', $this->phone);
            
            $this->processPhone();
            
            if(str_word_count($this->keyword) > 1){
                $this->keywords = explode(' ', $this->keyword);
            }else{
                $this->keywords = [$this->keyword];
            }
            
            if($this->dob !== null){
                $this->dobs = explode('-', $this->dob);
            }
            
            
        }
        
        private function processPhone(){
            
            foreach ($this->phones as $phone){
                $x = '';
                $split = str_split($phone);
                $length = sizeof($split);
                
                for ($i = 0; $i < count($split); $i++) {
                    $x .= $split[$i];
                    $this->phns[] = $x;
                }
                
                for ($i = count($split); $i >= 0; $i--) {
                    if($i == count($split)){
                        $this->phns[] = $split[$i];
                    }else{
                        $dif = count($split) - $i;
                        $num = '';
                        for ($j = 0; $j <= $dif; $j++) {
                            $num .= $split[$i + $j];
                        }
                        $this->phns[] = $num;
                    }
                }
            }
        }
        
        public function GenaratePassword(){
            $this->ByName();
            $this->ByKeyword();
            $this->ByPhone();
        }
        
        /*
        *    mix with
        *    @ dob
        *    @ phone
        */
        private function process($str){
            $this->passwords[] = $str;
            // name with dob 
            $this->mixer($str, $this->dobs);
            
            // only dob
            $this->passwords[] = str_replace('-', '', $this->dob);
            $this->passwords[] = str_replace('-', '.', $this->dob);
            
            
            // name with phone
            $this->mixer($str, $this->phns);
            
        }
        
        private function mixer($str, $array){
            $split = str_split($str);
            foreach ($array as $num){
                $sub_str = '';
                $this->passwords[] = $str.$num;
                $this->passwords[] = $num.$str;
                foreach ($this->symbols as $sym){
                    $this->passwords[] = $str.$sym.$num;
                    $this->passwords[] = $num.$sym.$str;
                }
                
                for ($j = 0; $j < count($split); $j++) {
                    $sub_str .= $split[$j];
                    $this->passwords[] = $sub_str.$num;
                    $this->passwords[] = $num.$sub_str;
                    foreach ($this->symbols as $sym){
                        $this->passwords[] = $sub_str.$sym.$num;
                        $this->passwords[] = $num.$sym.$sub_str;
                    }
                }
            }
        }
        
        private function str_mixer($array1, $array2){
            foreach ($array1 as $f){
                foreach ($array2 as $l){
                    $this->passwords[] = $f.$l;
                    $this->passwords[] = $l.$f;
                    foreach ($this->symbols as $sym){
                        $this->passwords[] = $f.$sym.$l;
                        $this->passwords[] = $l.$sym.$f;
                    }
                }
            }
        }
        
        private function case_mixer($str){
            $this->process(strtolower($str));
            $this->process(strtoupper($str));
            $this->process(ucfirst($str));
        }
        
        private function case_mixer_array($array1, $array2){
            $array1lower = array_map('strtolower', $array1);
            $array2lower = array_map('strtolower', $array2);
            $array1upper = array_map('strtoupper', $array1);
            $array2upper = array_map('strtoupper', $array2);
            
            $this->str_mixer($array1, $array2);
            $this->str_mixer($array2, $array1);
            $this->str_mixer($array1lower, $array2lower);
            $this->str_mixer($array2lower, $array1lower);
            $this->str_mixer($array2upper, $array1upper);
        }
        
        private function ByName(){
            $this->case_mixer_array($this->names, $this->names);
            
            foreach ($this->names as $name){
                $this->case_mixer($name);
            }
        }
        
        private function ByKeyword(){
            $this->case_mixer_array($this->names, $this->keywords);
            
            foreach ($this->keywords as $keyword){
                $this->case_mixer($keyword);
            }
            
            $this->case_mixer_array($this->names, $this->keywords);
        }
        
        private function ByPhone(){
            foreach ($this->phns as $phone){
                $this->passwords[] = $phone;
            }
        }
        
        private function run(){
            $this->GenaratePassword();
            
            foreach ($this->passwords as $key => $value) {
                if (strlen($value) < $this->minLen || strlen($value) > $this->maxLen) {
                    unset($this->passwords[$key]);
                }
            }
            
            $this->createFile();
        }
        
        public function createFile(){
            $file = $this->path.'/'.$this->file_name.'.txt';
            $data = 'hellow world;';
            $handle = fopen($file, "w+");
            foreach ($this->passwords as $pass){
                fwrite($handle, $pass.PHP_EOL);
            }
            fclose($handle);
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$file);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            
            //unlink($file);
        }
    }

