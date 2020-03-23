<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CryptographyOfJulioCesarController extends Controller
{
    protected $response;    
    protected $letters = [
        "a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"
    ];

    /**
     * Display a Score or Error.
     *
     * @return mixed
     */
    public function index()
    {
        $this->response = Http::get(env('COJC_URL') . env('COJC_GD') . env('COJC_TOKEN'));
        $this->storeJsonFile($this->response->json());
        $decrypted = $this->textDecrypt(json_decode($this->recoverJsonFile()));
        $json = $this->updateFieldDecifrado($decrypted);        
        $json = $this->storeJsonFile($json);                
        $crypt_sha1 = $this->textCrypt($decrypted);        
        $json = $this->updateFieldResumoCriptografico($crypt_sha1);
        $json = $this->storeJsonFile($json);        
        $json = $this->submitSolution();
                
        return $json;
    }

    /**
     * Store a json file as answer.json.
     * 
     * @return mixed
     */
    private function storeJsonFile($content)
    {
        $content = json_encode($content);
        Storage::put(env('COJC_FILENAME'), $content);

        return $this->recoverJsonFile();
    }

    /**
     * Recover a json file
     * 
     * @return mixed
     */
    private function recoverJsonFile()
    {
        return Storage::get(env('COJC_FILENAME'));
    }

    /**
     * Decrypt a text crypt
     * 
     * @return String
     */
    private function textDecrypt($content)
    {
        $words = explode(' ', $content->cifrado);
        $decrypted = [];

        foreach ($words as $word)
        {
            $word = str_split($word);            
            foreach($word as $letter)
            {
                if ($letter == ',' || $letter == '.')
                    $decrypted[] = $letter;

                else
                {
                    $i = array_keys($this->letters, $letter);
                    $i[0] = $i[0] - $content->numero_casas;

                    if ($i[0] >= 0)
                    {
                        $j = $this->letters[$i[0]];                        
                        $decrypted[] = $j;
                    }
                    else
                    {
                        $i[0] *= -1;
                        $j = $this->letters[26 - $i[0]];                        
                        $decrypted[] = $j;
                    }
                }
            }
            $decrypted[] = ' ';
        }

        $removed = array_pop($decrypted);
        $decrypted = implode('', $decrypted);

        return $decrypted;
    }

    /**
     * Update Field decifrado
     *
     * @param String $content
     * @return mixed
     */
    private function updateFieldDecifrado($content)
    {
        $json = json_decode($this->recoverJsonFile());
        $json->decifrado = $content;

        return $json;
    }

    /**
     * Crypt a plain text
     * 
     * @return String
     */
    private function textCrypt($content)
    {
        $crypt_sha1 = sha1($content);

        return $crypt_sha1;
    }

    /**
     * Update Field resumo criptografico
     *
     * @param String $content
     * @return mixed
     */
    private function updateFieldResumoCriptografico($content)
    {
        $json = json_decode($this->recoverJsonFile());
        $json->resumo_criptografico = $content;

        return $json;        
    }


    /**
     * Send Post Request
     *
     * @return mixed
     */
    private function submitSolution()
    {
        return $this->response = Http::attach('answer', Storage::get(env('COJC_FILENAME')), env('COJC_FILENAME'))
                                ->post(env('COJC_URL') . env('COJC_SS') . env('COJC_TOKEN'));
         
    }
}
