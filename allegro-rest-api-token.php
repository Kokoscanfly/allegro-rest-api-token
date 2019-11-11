<?php
class AllegroToken {

    function __construct($client_id, $secred_key, $url_back){
      $this->client_id = $client_id;
      $this->secred_key = $secred_key;
      $this->url_back = $url_back;
      //adres pod, ktorym uzyskamy kod autoryzacyjny potrzebny do uzyskania tokena
      $this->url_auth = "https://allegro.pl/auth/oauth/authorize";
      //adres pod, ktorym uzyskamy token na podstawie kodu autorazyjnego
      $this->url_token = "https://allegro.pl/auth/oauth/token";
      //naglowek header potrzebny do uzyskania tokena w formie tablicy
      $this->token_head = ['Authorization: Basic '.base64_encode($this->client_id.':'.$this->secred_key).''];
    }

    function Link(){
      //za pomoca wbudowanej funkcji http_build_query budujemy link, dla czytelniejszego kodu
      $url_auth_build = http_build_query([
                                  "response_type" => "code",
                                  "client_id" => $this->client_id,
                                  "redirect_uri" => $this->url_back,
                                  "prompt" => "confirm"
                            ]);
      return $this->url_auth.'?'.$url_auth_build;
    }

    function Generuj($code){
      //budujemy zapytanie POST, aby uzyskac token
      $token_post = http_build_query([
                            "grant_type" => "authorization_code",
                            "code" => $code,
                            "redirect_uri" => $this->url_back
                          ]);

      //inicjumeny zapytanie curl w php
      $curl = curl_init();
      //adres pod ktory wyslane zostaja dane
      curl_setopt($curl, CURLOPT_URL, $this->url_token);
      //naglowek zapytania
      curl_setopt($curl, CURLOPT_HTTPHEADER, $this->token_head);
      //informujemy ze wysylamy dane POST informujace ze chcemy cos utrzowyc (PUT oznacza modyfikacje)
      curl_setopt($curl, CURLOPT_POST, true);
      //informujemy, ze chcemy otrzymac informacje zwrotne
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      //wysyalmy wczesniej przygotowane dane POST
      curl_setopt($curl, CURLOPT_POSTFIELDS, $token_post);
      //wykonujemy nasze zapytanie curl, a wynik zaposujemy z zmiennej $output
      $wynik = curl_exec($curl);
      $kod_zapytania = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      //zamykamy polaczenie
      curl_close($curl);
      //polecenie dekodujace dane json na tablice czytelniejsza
      $dane = json_decode($wynik);

      //mala obsluga bledu
      if($kod_zapytania == 200){
        //zwracamy nasz tokem
        return $dane->access_token;
      }else{
        //zwracamy powstaly ewentualny blad
        return 'Wystąpił błąd: '.$kod_zapytania.' '.$dane->error.' '.$dane->error_description;
      }
    }
}


 ?>
<!DOCTYPE html>
<html>
<head></head>
<body>

<?php
//cliend id, ktory otrzymujemypo zarejestrowaniu aplikacji na https://apps.developer.allegro.pl/
$client_id = "...";
//secret key, ktory otrzymujemy po zarejestrowaniu aplikacji na https://apps.developer.allegro.pl/
$secred_key = "...";
//adres, na ktory skrypt ma powrocic po uzyskaniu kodu autoryzacyjnego. Adres zarejestowany na https://apps.developer.allegro.pl/
$url_back = "...";

$ak = new AllegroToken($client_id, $secred_key, $url_back);

//stworzony link (metoda GET) do wygenerowania kodu autorazyjnego wymaganego do uzyskania Tokena
//po kliknieciu otrzymaniu (rowniez metoda GET) kod autoryzacyjny
echo '<a href="'.$ak->link().'">Uzyskaj kod autoryzacyjny</a>';

//if sprawdzamy czy otrzymalismy zwrotny kod (jezeli tak, no to do dziela)
if(isset($_GET['code'])){
    //tą linią generowany jest token wazny 12godzin. Tym tokenem bedziemy sie autoryzowac przy zapytaniach, mozna zapisać go bazie
    echo $ak->Generuj($_GET['code']);
}
?>
</body>
</html>
