<?php
namespace App\Helper;

class Crypto
{
    public function decryp($request)
    {
        return $this->decryption($request);
    }


    public function encryp($request)
    {
        return $this->encryption($request);
    }



    private function encryption($request)
    {
        date_default_timezone_set("Europe/Paris");
        
        $requestA = $request;
        $Enckeys = array(    'A' => 'y',    'B' => 'I',    'C' => 'q',    'D' => 'T',    'E' => 9,    'F' => 'V',    'G' => 'J',    'H' => 'u',    'I' => 'c',    'J' => 'P',    'K' => 'a',    'L' => 4,    'M' => 2,    'N' => 'K',    'O' => 'S',    'P' => 'z',    'Q' => 'm',    'R' => 8,    'S' => 'j',    'T' => 'F',    'U' => 'g',    'V' => 5,    'W' => 'w',    'X' => 'b',    'Y' => 6,    'Z' => 'H',    'a' => 'v',    'b' => 'R',    'c' => 'O',    'd' => 'r',    'e' => 'D',    'f' => 'U',    'g' => 'Y',    'h' => 0,    'i' => 'h',    'j' => 3,    'k' => 'Q',    'l' => 's',    'm' => 't',    'n' => 'C',    'o' => 'l',    'p' => 7,    'q' => 'W',    'r' => 'i',    's' => 'k',    't' => 'L',    'u' => 'd',    'v' => 'M',    'w' => 'e',    'x' => 'x',    'y' => 1,    'z' => 'G',    '0' => 'p',    '1' => 'X',    '2' => 'o',    '3' => 'B',    '4' => 'A',    '5' => 'E',    '6' => 'f',    '7' => 'Z',    '8' => 'n',    '9' => 'N');
       
        $currentDate = strtotime(date("Y-m-d H:i:s"));
    
        $futureDate = $currentDate+(60000);
        
        $formatDate = date("Y-m-d H:i:s", $futureDate);
       
        
        $requestA.="#dte#".$formatDate;
        $arr1 = str_split($requestA);
        $enc="";
        foreach ($arr1 as $key => $value) {
            if (ctype_alnum($value)) {
                $enc.=$Enckeys[$value];
            } else {
                $enc.=$value;
            }
        }
        $hash=sha1($enc);
        $enc.="!h!".$hash;
       
        $data = array("message"=>base64_encode($enc));
        return $data;
    }
        

    private function decryption($request)
    {
        date_default_timezone_set("Europe/Paris");
        $Enckeys = array(    'A' => 'y',    'B' => 'I',    'C' => 'q',    'D' => 'T',    'E' => 9,    'F' => 'V',    'G' => 'J',    'H' => 'u',    'I' => 'c',    'J' => 'P',    'K' => 'a',    'L' => 4,    'M' => 2,    'N' => 'K',    'O' => 'S',    'P' => 'z',    'Q' => 'm',    'R' => 8,    'S' => 'j',    'T' => 'F',    'U' => 'g',    'V' => 5,    'W' => 'w',    'X' => 'b',    'Y' => 6,    'Z' => 'H',    'a' => 'v',    'b' => 'R',    'c' => 'O',    'd' => 'r',    'e' => 'D',    'f' => 'U',    'g' => 'Y',    'h' => 0,    'i' => 'h',    'j' => 3,    'k' => 'Q',    'l' => 's',    'm' => 't',    'n' => 'C',    'o' => 'l',    'p' => 7,    'q' => 'W',    'r' => 'i',    's' => 'k',    't' => 'L',    'u' => 'd',    'v' => 'M',    'w' => 'e',    'x' => 'x',    'y' => 1,    'z' => 'G',    '0' => 'p',    '1' => 'X',    '2' => 'o',    '3' => 'B',    '4' => 'A',    '5' => 'E',    '6' => 'f',    '7' => 'Z',    '8' => 'n',    '9' => 'N');
     
    
        $request=base64_decode($request);
        $hashedrequest=explode('!h!', $request);
         
        if (sha1($hashedrequest[0])!=$hashedrequest[1]) {
            return response()->json([
                 'success' => false,
                 'message' => 'Request was modified by other person',

                 'status' => 400
             ], 400);
        }
    

        $flipedEnckeys = array_flip($Enckeys);
        $arr1 = str_split($hashedrequest[0]);
        $enc="";
        foreach ($arr1 as $key => $value) {
            if (ctype_alnum($value)) {
                $enc.=$flipedEnckeys[$value];
            } else {
                $enc.=$value;
            }
        }
        
         
        $expdate = explode('#dte#', $enc);
      
       
        if (strtotime($expdate[1]) >= strtotime(date("Y-m-d H:i:s"))) {
            $data = $expdate[0];
            
            return response()->json([
                'success' => true,
                'message' => $data,
    
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                    'success' => false,
                    'message' => 'This request has been expired',
    
                    'status' => 400
                ], 400);
        }
    }
}
