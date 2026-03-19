<?php
/*
  --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
   --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
  --------------------------------------------------------------------------
  Questo programma e` free software;   e` lecito redistribuirlo  e/o
  modificarlo secondo i  termini della Licenza Pubblica Generica GNU
  come e` pubblicata dalla Free Software Foundation; o la versione 2
  della licenza o (a propria scelta) una versione successiva.

  Questo programma  e` distribuito nella speranza  che sia utile, ma
  SENZA   ALCUNA GARANZIA; senza  neppure  la  garanzia implicita di
  NEGOZIABILITA` o di  APPLICABILITA` PER UN  PARTICOLARE SCOPO.  Si
  veda la Licenza Pubblica Generica GNU per avere maggiori dettagli.

  Ognuno dovrebbe avere   ricevuto una copia  della Licenza Pubblica
  Generica GNU insieme a   questo programma; in caso  contrario,  si
  scriva   alla   Free  Software Foundation, 51 Franklin Street,
  Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
  --------------------------------------------------------------------------
 */

 // creo connessione al DB
 function db_connect() {
    static $link = null;
    if ($link !== null && $link->ping()) {
        return $link; // riusa la connessione già aperta
    }
    $servername = constant("Host");
    $username   = constant("User");
    $pass       = constant("Password");
    $dbname     = constant("Database");
    $link = mysqli_connect($servername, $username, $pass, $dbname);
    if (!$link) {
        die("Connection DB failed: " . mysqli_connect_error());
    }
    $link->set_charset("utf8mb4");
    return $link;
}

// restituisce il tipo di dispositivo usato dall'utente.
/*
app = App Android “AppGmonamour”
mobile = Smartphone o cookie mobile
tablet = Tablet o cookie tablet
desktop = PC/Laptop o cookie desktop
unknown = Bot, UA non riconosciuto, UA vuoto
*/
function detect_device($app_name=''): string{
    $server = $_SERVER;
    if (!empty($app_name)){

    }else{
      $app_name = 'AppGmonamour';
    }

    if (!empty($server['HTTP_USER_AGENT']) &&
        stripos($server['HTTP_USER_AGENT'], $app_name) !== false) {
        return 'app';
    }

    $ua = strtolower($server['HTTP_USER_AGENT'] ?? '');
    if ($ua === '') return 'unknown';

    if (preg_match('/bot|spider|crawl|slurp|facebookexternalhit|mediapartners-google/', $ua)) {
        return 'unknown';
    }

    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipod') !== false) return 'mobile';

    if (strpos($ua, 'ipad') !== false ||
        (strpos($ua, 'macintosh') !== false &&
         strpos($ua, 'mobile') === false &&
         strpos($ua, 'safari') !== false)) return 'tablet';

    if (strpos($ua, 'android') !== false) return (strpos($ua, 'mobile') !== false) ? 'mobile' : 'tablet';

    if (preg_match('/windows phone|iemobile|blackberry|bb10|opera mini|opera mobi/', $ua)) return 'mobile';

    if (preg_match('/windows nt|macintosh|x11|linux|cros/', $ua)) return 'desktop';

    return 'unknown';
}

function initTrackingState(array &$data) { // true = possiamo tracciare, false = proibito
    global $link;
	// percorso file debug
	$debugFile = __DIR__ . '/tracking_debug.txt';
	//file_put_contents($debugFile, date('Y-m-d H:i:s') . " - initTrackingState inizio session: " . json_encode($_SESSION) . " - cookie:". json_encode($_COOKIE['tracking_session']) . PHP_EOL, FILE_APPEND);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();    }
      //echo "<br>Debug: Inizio initTrackingState";
    // 🔁 Se già determinato in questa sessione, non rifare query
    if (isset($_SESSION['can_track']) && !empty($data['user_id'])) {
        return $_SESSION['can_track'];
    }
    $canTrack = false;
	if (empty($_SESSION['token_Android']) && isset($_COOKIE['tracking_session'])){// se non ho session ma ho cookie
		$_SESSION['token_Android']=$_COOKIE['tracking_session'];
		//file_put_contents($debugFile, date('Y-m-d H:i:s') . " - initTrackingState metto il cookie in session " . PHP_EOL, FILE_APPEND);

	}
    // 📱 ANDROID: controllo token dispositivo
    if (!empty($_SESSION['token_Android'])) {
        $token = $_SESSION['token_Android'];
		//file_put_contents($debugFile, date('Y-m-d H:i:s') . " - initTrackingState controllo il token: " . $token . PHP_EOL, FILE_APPEND);

        $stmt = $link->prepare("
			SELECT permission, user_id
			FROM gaz_android_device_tokens
			WHERE token = ?
			LIMIT 1
		");

		if ($stmt) {
			$stmt->bind_param("s", $token);
			$stmt->execute();
			$stmt->bind_result($permission, $dbUserId);
            if ($stmt->fetch() && $permission == 1) {
                $canTrack = true;
                //echo "<br>Debug: Android consentito";
				// Se $data['user_id'] non è valorizzato ma il DB ha un valore
				if (isset($data) && empty($data['user_id']) && !empty($dbUserId)) {
					$data['user_id'] = intval($dbUserId);
				}
				//file_put_contents($debugFile, date('Y-m-d H:i:s') . " - function data: " . json_encode($data) . PHP_EOL, FILE_APPEND);
            } else {
                //echo "<br>Debug: Android NON consentito";
				//file_put_contents($debugFile, date('Y-m-d H:i:s') . " initTrackingState - tracciamento non consentito permission: " . $permission . json_encode($data). PHP_EOL, FILE_APPEND);

            }
            $stmt->close();
        }else{
			//file_put_contents($debugFile, date('Y-m-d H:i:s') . " initTrackingState - dispositivo non trovato token: " . $token . PHP_EOL, FILE_APPEND);

		}

		//file_put_contents($debugFile, date('Y-m-d H:i:s') . " - initTrackingState cantrack: " . $canTrack . " -session Andr:". $_SESSION['token_Android'] . PHP_EOL, FILE_APPEND);
    }
    // 🖥 DESKTOP / BROWSER: utente identificato
    elseif (!empty($_COOKIE['tracking_id']) || !empty($_SESSION['logged'])) {
        //echo "<br>Debug: utente identificato, controllo consenso";
        // ✅ Controllo tracking_id
        if (!empty($_COOKIE['tracking_id'])) {
            $id = (int)$_COOKIE['tracking_id'];
            $stmt = $link->prepare("
                SELECT consent_profiling
                FROM gaz_marketing_identity
                WHERE id = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->bind_result($consent_profiling);
                if ($stmt->fetch() && $consent_profiling == 1) {
                    $canTrack = true;
                    //echo "<br>Debug: tracking_id consentito";
                } else {
                    //echo "<br>Debug: tracking_id NON consentito";
                }
                $stmt->close();
            }
        }
        // ✅ Controllo user_id
        if (!$canTrack && !empty($_SESSION['logged'])) {
			$user_id = hex2bin($_SESSION['logged']);

			$stmt = $link->prepare("
				SELECT id, consent_profiling
				FROM gaz_marketing_identity
				WHERE user_id = ?
				LIMIT 1
			");

			$canTrack = false; // valore di default

			if ($stmt) {
				$stmt->bind_param("s", $user_id);
				$stmt->execute();

				// bind sia l'id che il consent_profiling
				$stmt->bind_result($id, $consent_profiling);

				if ($stmt->fetch()) {
					// record trovato
					if ($consent_profiling == 1) {
						$canTrack = true;
						// rinfresco cookie tecnico 1 anno
						setcookie('tracking_id', $id, time() + 31536000, "/", "", false, true); // 1 anno
						//echo "<br>Debug: user_id consentito";
					} else {
						// echo "<br>Debug: user_id NON consentito";
						// $canTrack resta false
					}
				} else {
					// record NON trovato → consideriamo come consenso dato
					$canTrack = true;
					// Non abbiamo un $id dal DB, quindi non possiamo settare il cookie
					// (facoltativo: puoi generare un ID tecnico random se vuoi il cookie)
				}

				$stmt->close();
			}
		}
        if (!$canTrack) {
            //echo "<br>Debug: utente identificato senza consenso, blocco tracciamento";
        }
    }
    // 🌐 Sessione anonima pura: niente tracking_id, niente user_id
    if (!$canTrack && empty($_COOKIE['tracking_id']) && empty($_SESSION['logged'])) {
        $canTrack = true;
        //echo "<br>Debug: sessione anonima, tracciamento consentito";
    }
    $_SESSION['can_track'] = $canTrack;
    //echo "<br>Debug: fine controllo, canTrack = " . ($canTrack ? 'true' : 'false');
    return $canTrack;
}

/**
 * Traccia evento utente per app o sito
 */
function trackUserEvent(array $data){
    global $link, $azTables;

    $debugFile = __DIR__ . '/tracking_debug.txt';
    file_put_contents($debugFile, date('Y-m-d H:i:s') . " - trackUserEvent RECEIVED: " . json_encode($data) . PHP_EOL, FILE_APPEND);

    // 1️⃣ Gestione anonimizzazione
    if (!initTrackingState($data)) {
        $data['user_id'] = null;
        $data['device_uuid'] = null;
        $data['session_id'] = session_id();
    }
    file_put_contents($debugFile, date('Y-m-d H:i:s') . " - trackUserEvent dopo initTrackingState: " . json_encode($data) . PHP_EOL, FILE_APPEND);

    // 2️⃣ Identificazione device / sessione
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $device = $data['device_uuid'] ?? 'unknown';
    $session = $data['session_id'] ?? '';
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : null;
    $ip_hash = sha1($_SERVER['REMOTE_ADDR']);
    $user_agent = $data['user_agent'] ?? $ua;
    $cookie_session = $_COOKIE['tracking_session'] ?? null;
	$today = date('Y-m-d'); // giorno corrente

    if (!$device && !$session && !is_numeric($user_id)) return false;

    // 3️⃣ Escape una volta sola
    $device_esc = $link->real_escape_string($device);
    $session_esc = $link->real_escape_string($session);
    $user_agent_esc = $link->real_escape_string($user_agent);
    $ip_hash_esc = $link->real_escape_string($ip_hash);
    $tracking_session = $data['tracking_session'] ?? $cookie_session;
    $tracking_session_esc = $tracking_session ? $link->real_escape_string($tracking_session) : null;
	$data['tracking_session'] = (isset($tracking_session_esc)) ? $tracking_session_esc: $cookie_session;

    // 4️⃣ Costruzione WHERE per hard match (versione corretta)
	$where = [];

	// Match su device_uuid, se presente
	if (!empty($device_esc)) {
		$where[] = "device_uuid='$device_esc'";
	}

	// Match su user_id, se presente
	if (is_numeric($user_id)) {
		$where[] = "user_id=$user_id";
	}

	// Match combinato su session_id + tracking_session
	if (!empty($session_esc) && !empty($tracking_session_esc)) {
		$where[] = "(session_id='$session_esc' AND tracking_session='$tracking_session_esc')";
	} elseif (!empty($session_esc)) {
		$where[] = "session_id='$session_esc'";
	} elseif (!empty($tracking_session_esc)) {
		$where[] = "tracking_session='$tracking_session_esc'";
	}

	// Filtro sulla data di oggi
	//$where[] = "DATE(created_at) = '$today'";

    // 5️⃣ Trova ultima riga
    $last_row = null;
    if (!empty($where)) {
        $where_sql = implode(' OR ', $where); // OR perché basta che una condizione coincida
        $res = $link->query("SELECT * FROM gaz_track_events WHERE $where_sql ORDER BY created_at DESC LIMIT 1");
        if ($res && $res->num_rows) $last_row = $res->fetch_assoc();
    }

    // 6️⃣ Soft match (solo se non device_uuid)
    if (!$last_row && empty($device_esc)) {
        $time_window = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $query = "
            SELECT *
            FROM gaz_track_events
            WHERE user_id IS NULL
              AND ip_hash='$ip_hash_esc'
              AND user_agent='$user_agent_esc'
              AND created_at >= '$time_window'
            ORDER BY created_at DESC
            LIMIT 1
        ";
        $res = $link->query($query);
        if ($res && $res->num_rows) $last_row = $res->fetch_assoc();
    }

    // 7️⃣ Prepara valori SQL
    $search_location = isset($data['search_location']) ? $link->real_escape_string($data['search_location']) : null;
    $search_start_date = isset($data['search_start_date']) ? date('Y-m-d', strtotime($data['search_start_date'])) : null;
    $search_end_date = isset($data['search_end_date']) ? date('Y-m-d', strtotime($data['search_end_date'])) : null;
    $guests = isset($data['guests']) ? intval($data['guests']) : null;
    $room_type = isset($data['room_type']) ? $link->real_escape_string($data['room_type']) : null;
    $property_id = isset($data['property_id']) ? intval($data['property_id']) : null;
    $booking_value = isset($data['booking_value']) ? floatval($data['booking_value']) : null;
    $valid_status = ['started','completed','abandoned'];
    $booking_status = (isset($data['booking_status']) && in_array($data['booking_status'],$valid_status)) ? $link->real_escape_string($data['booking_status']) : null;
    $first_booking_at = isset($data['first_booking_at']) ? $link->real_escape_string($data['first_booking_at']) : null;
    $last_property_viewed = isset($data['last_property_viewed']) ? intval($data['last_property_viewed']) : null;
    $source = isset($data['source']) ? $link->real_escape_string($data['source']) : null;
    $campaign = isset($data['campaign']) ? $link->real_escape_string($data['campaign']) : null;
    $app_version = isset($data['app_version']) ? $link->real_escape_string($data['app_version']) : null;
    $platform = detect_device();
    $lang = isset($data['lang']) ? $link->real_escape_string($data['lang']) : null;

    $isSearch = $search_start_date && $search_end_date;

    // 8️⃣ Costruisci evento figlio
    $child_event = [];
    foreach ($data as $k=>$v){
        if (in_array($k,['device_uuid','session_id','user_id','user_agent','ip_hash'])) continue;
        if ($v === null || $v === '') continue;
        if (in_array($k,['property_id','guests','last_property_viewed'])) $v = intval($v);
        if ($k === 'booking_value') $v = floatval($v);
        $child_event[$k]=$v;
    }
    $child_event['timestamp'] = date('Y-m-d H:i:s');

    // Funzione helper per merge payload
    $mergePayload = function($last_row, $child_event) use($link){
        $existing = $last_row['event_payload'] ? json_decode($last_row['event_payload'], true) : [];
        $existing[] = $child_event;
        return $link->real_escape_string(json_encode($existing, JSON_UNESCAPED_UNICODE));
    };

    // 9️⃣ Se ultima riga esiste → UPDATE
    if ($last_row){
        $lastWasSearch = $last_row['search_start_date'] && $last_row['search_end_date'];
        $appendChild = true;

        if ($isSearch && !$lastWasSearch) {
            // Trasforma ultima riga in ricerca
            $last_row['search_start_date'] = $search_start_date;
            $last_row['search_end_date'] = $search_end_date;
        } elseif ($isSearch && $lastWasSearch &&
                  $last_row['search_start_date']==$search_start_date &&
                  $last_row['search_end_date']==$search_end_date){
            // Stesso search → append child
        } else {
            // Altro evento → append child
        }

        $merged_payload_json = $mergePayload($last_row, $child_event);
        $update_user = ($last_row['user_id']===null && is_numeric($user_id)) ? ", user_id=$user_id" : "";

        $sql = "
            UPDATE gaz_track_events SET
                tracking_session = ".($tracking_session_esc ? "'$tracking_session_esc'" : 'NULL').",
                search_location = ".($search_location ? "'$search_location'" : 'NULL').",
                search_start_date = ".($last_row['search_start_date'] ? "'".$last_row['search_start_date']."'" : 'NULL').",
                search_end_date   = ".($last_row['search_end_date']   ? "'".$last_row['search_end_date']."'"   : 'NULL').",
                guests = ".($guests!==null ? $guests : 'NULL').",
                room_type = ".($room_type ? "'$room_type'" : 'NULL').",
                property_id = ".($property_id!==null ? $property_id : 'NULL').",
                booking_value = ".($booking_value!==null ? $booking_value : 'NULL').",
                booking_status = ".($booking_status ? "'$booking_status'" : 'NULL').",
                first_booking_at = ".($booking_status ? "NOW()" : ($first_booking_at ? "'$first_booking_at'" : 'NULL')).",
                last_property_viewed = ".($last_property_viewed!==null ? $last_property_viewed : 'NULL').",
                source = ".($source ? "'$source'" : 'NULL').",
                campaign = ".($campaign ? "'$campaign'" : 'NULL').",
                platform = ".($platform ? "'".$link->real_escape_string($platform)."'" : 'NULL').",
                ip_hash = '$ip_hash_esc',
                user_agent = '$user_agent_esc',
                lang = ".($lang ? "'$lang'" : 'NULL').",
                event_payload = '$merged_payload_json',
                last_event_at = NOW()
                $update_user
            WHERE id=".$last_row['id']."
        ";
        return $link->query($sql);
    }

    //  🔟 Nessuna riga → INSERT
    $event_payload = json_encode([$child_event], JSON_UNESCAPED_UNICODE);

    $sql = "
        INSERT INTO gaz_track_events (
            device_uuid, session_id, user_id,
            tracking_session,
            event_payload,
            search_location, search_start_date, search_end_date, guests, room_type, property_id,
            booking_value, booking_status, first_booking_at,
            last_property_viewed,
            source, campaign,
            platform, ip_hash, user_agent,
            lang
        ) VALUES (
            '$device_esc',
            ".($session ? "'$session_esc'" : 'NULL').",
            ".(is_numeric($user_id)?$user_id:'NULL').",
            ".($tracking_session_esc?"'$tracking_session_esc'":'NULL').",
            '".$link->real_escape_string($event_payload)."',
            ".($search_location?"'$search_location'":'NULL').",
            ".($search_start_date?"'$search_start_date'":'NULL').",
            ".($search_end_date?"'$search_end_date'":'NULL').",
            ".($guests!==null?$guests:'NULL').",
            ".($room_type?"'$room_type'":'NULL').",
            ".($property_id!==null?$property_id:'NULL').",
            ".($booking_value!==null?$booking_value:'NULL').",
            ".($booking_status?"'$booking_status'":'NULL').",
            ".($first_booking_at?"'$first_booking_at'":'NULL').",
            ".($last_property_viewed!==null?$last_property_viewed:'NULL').",
            ".($source?"'$source'":'NULL').",
            ".($campaign?"'$campaign'":'NULL').",
            ".($platform?"'".$link->real_escape_string($platform)."'":'NULL').",
            '$ip_hash_esc',
            '$user_agent_esc',
            ".($lang?"'$lang'":'NULL')."
        )
    ";
    return $link->query($sql);
}



function iCalDecoder($file) {
    $ical = @file_get_contents($file);
	preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/si', $ical, $result, PREG_PATTERN_ORDER);
    for ($i = 0; $i < count($result[0]); $i++) {
      $tmpbyline = explode("\r\n", $result[0][$i]);
      if (count($tmpbyline)<3){// se non sono riuscito a separare i righi con \r\n
        $tmpbyline = explode("\n", $result[0][$i]); // provo solo con \n"
      }
      foreach ($tmpbyline as $item) {
        if (substr($item,0,7)=="DTSTART"){
            $majorarray['start']=substr($item,19,10);
        }
        if (substr($item,0,5)=="DTEND"){
            $majorarray['end']=substr($item,17,10);
        }
        if (substr($item,0,3)=="UID"){
            $majorarray['uid']=substr($item,3);
        }
      }
      $icalarray[] = $majorarray;
      unset($majorarray);
    }

    return !empty($icalarray) ? $icalarray : false;

}

// controlla se il numero carta di credito è corretto
function validatecard($cardnumber) {// L' algoritmo di Luhn , noto anche come algoritmo 10, è una semplice checksum utilizzata per convalidare numeri di identificazione, come il numero delle carte di credito
    $cardnumber=preg_replace("/\D|\s/", "", $cardnumber);  # strip any non-digits
    $cardlength=strlen($cardnumber);
    if($cardlength>0){// previene esito positivo se non è stato passato nulla
      $parity=$cardlength % 2;
      $sum=0;
      for ($i=0; $i<$cardlength; $i++) {
        $digit=$cardnumber[$i];
        if ($i%2==$parity) $digit=$digit*2;
        if ($digit>9) $digit=$digit-9;
        $sum=$sum+$digit;
      }
    $valid=($sum%10==0);
    }else{
      $valid=null;
    }
    return $valid;
}

// Ricerca gli sconti applicabili -> vengono esclusi i buoni sconto
function searchdiscount($house="",$facility="",$start="",$end="",$stay=0,$anagra=0,$table="",$booking_start="",$booking_end=""){
  global $link, $azTables;
  if ($table == ""){
	  $table = $azTables."rental_discounts";
  }
  $where=" ";
  $and=" WHERE (";
  if (strlen($house)>0){
    $where .= $and." accommodation_code = '".mysqli_real_escape_string($link,$house)."' OR accommodation_code='')";
    $and=" AND (";
  }
  if (intval($facility)>0){
    $where .= $and." facility_id = ". intval($facility) ." OR facility_id = 0)";
    $and=" AND (";
  }
  if (intval($start)>0){
    $where .= $and." valid_from <= '".date("Y-m-d", strtotime($start))."' OR valid_from = '0000-00-00')";
    $and=" AND (";
  }
  if (intval($end)>0){
    $where .= $and." valid_to >= '".date("Y-m-d", strtotime($end))."' OR valid_to = '0000-00-00')";
    $and=" AND (";
  }
  if (!empty($booking_start) && !empty($booking_end)) {
    $startDate = date("Y-m-d", strtotime($booking_start));
    $endDate = date("Y-m-d", strtotime($booking_end));

    $where .= $and."
        (booking_start <= '".$startDate."' OR booking_start = '0000-00-00')
        AND
        (booking_end >= '".$endDate."' OR booking_end = '0000-00-00')
      )";
    $and = " AND (";
  }
  if (intval($stay)>0){
    $where .= $and." min_stay <= ".intval($stay)." OR min_stay = 0)";
    $and=" AND (";
  }
  if (intval($anagra)>0){
    $where .= $and." id_anagra = ".intval($anagra)." OR id_anagra = 0)";
    $and=" AND (";
  }
  $where .= $and." status = 'CREATED' AND (discount_voucher_code = '' OR discount_voucher_code is NULL ))";

  $sql = "SELECT *, JSON_UNQUOTE(JSON_EXTRACT(custom_field, '$.vacation_rental.app_name')) AS app_name FROM ".$table.$where." ORDER BY priority DESC, id ASC";
  //echo "<br>query search discount: ",$sql,"<br>";
  if ($result = mysqli_query($link, $sql)) {
    return ($result);
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

// Ricerca gli sconti più vicini (riferito ai giorni di permanenza) -> vengono esclusi i buoni sconto
function search_near_discount($house="",$facility="",$start="",$end="",$stay=0,$anagra=0,$table=""){
  global $link, $azTables;
  if ($table == ""){
	  $table = $azTables."rental_discounts";
  }
  $where=" ";
  $and=" WHERE (";
  if (strlen($house)>0){
    $where .= $and." accommodation_code = '".$house."' OR accommodation_code='')";
    $and=" AND (";
  }
  if (intval($facility)>0){
    $where .= $and." facility_id = '".$facility."' OR facility_id = 0)";
    $and=" AND (";
  }
  if (intval($start)>0){
    $where .= $and." valid_from <= '".date("Y-m-d", strtotime($start))."' OR valid_from = '0000-00-00')";
    $and=" AND (";
  }
  if (intval($end)>0){
    $where .= $and." valid_to >= '".date("Y-m-d", strtotime($end))."' OR valid_to = '0000-00-00')";
    $and=" AND (";
  }
  if (intval($stay)>0){
    $near_stay = $stay+3;
    $where .= $and." min_stay <= '".$near_stay."' AND min_stay > '".$stay."' )";
    $and=" AND (";
  }
  if (intval($anagra)>0){
    $where .= $and." id_anagra = '".$anagra."' OR id_anagra = 0)";
    $and=" AND (";
  }
  $where .= $and." status = 'CREATED' AND (discount_voucher_code = '' OR discount_voucher_code = NULL ))";
  $sql = "SELECT * FROM ".$table.$where." ORDER BY priority DESC, id ASC";
  //echo "<br>query: ",$sql,"<br>";
  if ($result = mysqli_query($link, $sql)) {
    return ($result);
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

// come selectFromDB ma permette di fare join
function selectFromDBJoin($table, $name, $key, $val, $order = false, $empty = false, $bridge = '', $key2 = '', $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style = '', $where = false, $echo=false) {
        global $gTables;
		$acc='';
        $refresh = '';
        if (!$order) {
            $order = $key;
        }
        $query = 'SELECT * FROM ' . $table . ' ';
        if ($where) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `' . $order . '`';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        $acc .= "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            $acc .= "\t\t <option value=\"\"></option>\n";
        }

        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($r[$key] == $val) {
                $selected = "selected";
            }
            $acc .= "\t\t <option value=\"" . $r[$key] . "\" $selected >";
            if (empty($key2)) {
                $acc .= substr($r[$key], 0, 43) . "</option>\n";
            } else {
                $acc .= substr($r[$key], 0, 28) . $bridge . substr($r[$key2], 0, 35) . "</option>\n";
            }
        }
        if ($addOption) {
            $acc .= "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                $acc .= " selected ";
            }
            $acc .= ">" . $addOption['descri'] . "</option>\n";
        }
        $acc .= "\t </select>\n";
		if ($echo){
			return $acc;
		} else {
			echo $acc;
		}
}
function get_string_lang($string, $lang){
    // se non ci sono tag lingua, restituisco la stringa così com'è
    if (strpos($string, '<') === false || strpos($string, '>') === false) {
        return $string;
    }
    // cerco se è presente almeno un tag <xx>...</xx>
    if (!preg_match('/<([a-z]{2})>.*?<\/\1>/is', $string)) {
        return $string;
    }
    // prova a estrarre il tag richiesto
    if (preg_match('/<' . preg_quote($lang, '/') . '>(.*?)<\/' . preg_quote($lang, '/') . '>/is', $string, $m)) {
        return $m[1];
    }
    // se non trovato, prova a restituire l'inglese se presente
    if (preg_match('/<en>(.*?)<\/en>/is', $string, $m_en)) {
        return $m_en[1];
    }
    // se ci sono tag lingua ma né la lingua richiesta né "en", restituisco comunque la stringa originale
    return $string;
}

function get_lang_translation($ref, $table, $lang_id){// nuovo sistema traduzione tramite tabella body-text
    if ($lang_id>1){// traduco solo se non è la lingua di default
      global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
      if ($azTables){
        $table_body= $azTables."body_text";
      }else{
        $table_body= $gTables['body_text'];
      }
      $where = " WHERE (table_name_ref = '".$table."' AND code_ref = '".substr($ref,0,32)."' AND lang_id = ".$lang_id.")";
      $sql = "SELECT * FROM ".$table_body.$where." LIMIT 1";
      if ($result = mysqli_query($link, $sql)) {
        $bodytextlang = mysqli_fetch_assoc($result);
      }else{
        echo "Error: " . $sql . "<br>" . mysqli_error($link);
      }
      if (is_array($bodytextlang)){
      $ret=array();
      $ret['descri'] = (isset($bodytextlang['descri']))?$bodytextlang['descri']:'';
      $ret['body_text'] = (isset($bodytextlang['body_text']))?$bodytextlang['body_text']:'';
      $obj = $bodytextlang?json_decode($bodytextlang['custom_field']):false;
      $ret['web_url'] = (isset($obj->web_url))?$obj->web_url:'';
      if (isset($obj->check_in)){
        $ret['check_in']=$obj->check_in;
      }
      if (isset($obj->check_out)){
        $ret['check_out']=$obj->check_out;
      }
      return $ret;
      }else{
        return false;
      }
    }else{
      return false;
    }
}

// calcolo dei giorni da pagare per la tassa turistica fra due date specifiche
function tour_tax_daytopay($start, $end, $tour_tax_from, $tour_tax_to, $tour_tax_day = 0, $full_start = null, $full_end = null) {
  if (empty($tour_tax_from) || empty($tour_tax_to)) {
      // Tassa valida tutto l'anno: 1 gennaio - 31 dicembre
      $tour_tax_from = "01-01";
      $tour_tax_to   = "31-12";

      $startYear = date("Y", strtotime($start));
      $endYear = date("Y", strtotime($end));

      if ($endYear > $startYear) {
          $tour_tax_to = "31-12";
          $multi_year = true;
      } else {
          $multi_year = false;
      }
  }
  $year = date("Y", strtotime($start));
	$from_parts = explode('-', $tour_tax_from); // es: ['15', '12']
	$to_parts   = explode('-', $tour_tax_to);   // es: ['15', '01']

	$from_date = new DateTime("$year-{$from_parts[1]}-{$from_parts[0]}");
  $to_date   = new DateTime("$year-{$to_parts[1]}-{$to_parts[0]}");

  // Estendi il periodo tassa se attraversa l’anno o se è tassa "tutto l’anno" su soggiorno multi-year
  if ($to_date < $from_date || (!empty($multi_year) && $multi_year)) {
      $to_date->modify('+1 year');
  }

	$tour_tax_from = $from_date->format('Y-m-d');
	$tour_tax_to   = $to_date->format('Y-m-d');


	$night = (new DateTime($start))->diff(new DateTime($end))->days;
    $daytopay = intval($night); // default: tutte le notti si pagano, se non c'è un periodo specifico

    if (strtotime($tour_tax_from)) { // Se c'è un periodo specifico per la tassa
        $start_date = new DateTime($start);
        $end_date = new DateTime($end);
        $tax_start = new DateTime($tour_tax_from);
        $tax_end = new DateTime($tour_tax_to);

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $end_date);

        $count = 0;
        foreach ($period as $night_date) {
            if ($night_date >= $tax_start && $night_date <= $tax_end) {
                $count++;
            }
        }

        $daytopay = $count; // sovrascrivo solo se c'è un periodo valido
    }

	// ** LOGICA PER IL CALCOLO STATISTICO **
    // Se viene passato anche il periodo completo locazione, controllo se start e end
    // rientrano nelle prime tour_tax_day notti dalla locazione completa.
    if (strtotime($tour_tax_from) && strtotime($tour_tax_to) && $full_start !== null && $full_end !== null && intval($tour_tax_day) > 0) {
      $full_start_dt = new DateTime($full_start);
      $full_end_dt = new DateTime($full_end);
      $start_dt = new DateTime($start);
      $end_dt = new DateTime($end);
      $tax_start = new DateTime($tour_tax_from);
      $tax_end = new DateTime($tour_tax_to);

      // Iteriamo l'intero soggiorno (full_start → full_end), ma consideriamo solo i giorni nel periodo tassa
      $interval = new DateInterval('P1D');
      $full_period = new DatePeriod($full_start_dt, $interval, $full_end_dt);

      $tassabili = [];
      foreach ($full_period as $d) {
        if ($d >= $tax_start && $d <= $tax_end) {
          $tassabili[] = $d->format('Y-m-d');
        }
      }

      // Prendiamo solo le prime X notti tassabili
      $tassabili_limitate = array_slice($tassabili, 0, intval($tour_tax_day));

      // Ora verifichiamo se $start (giorno singolo, nel caso statistico) rientra in quelle notti tassabili
      $giorno_analizzato = $start_dt->format('Y-m-d');

      if (in_array($giorno_analizzato, $tassabili_limitate)) {
        $daytopay = 1;
      } else {
        $daytopay = 0;
      }
    }
    // Applico limite massimo di notti da pagare
    if (intval($tour_tax_day) > 0 && $daytopay > intval($tour_tax_day)) {
        $daytopay = intval($tour_tax_day);
    }
    return $daytopay;
}


// CALCOLO PREZZO TOTALE LOCAZIONE
//NB: tourist_tax e add_extra funzionano solo con $vat = FALSE
//NB: Security deposit funziona solo con $vat = TRUE
function get_totalprice_booking($tesbro, $tourist_tax = TRUE, $vat = FALSE, $preeminent_vat = "", $add_extra = FALSE, $security_deposit = FALSE) {
    if ($tesbro !== '') {
        $tesbro = intval($tesbro);
        global $link, $azTables, $gTables;

        if ($azTables) {
            $tablerig = $azTables . "rigbro";
            $tabletes = $azTables . "tesbro";
            $tableiva = $azTables . "aliiva";
            $tableaz = $azTables . "aziend";
            $tableart = $azTables . "artico";
        } else {
            $tablerig = $gTables['rigbro'];
            $tabletes = $gTables['tesbro'];
            $tableiva = $gTables['aliiva'];
            $tableaz = $gTables['aziend'];
            $tableart = $gTables['artico'];
        }

        // Leggo il deposito cauzionale da tesbro
        $sql = "SELECT custom_field FROM " . $tabletes . " WHERE id_tes = " . $tesbro . " LIMIT 1";
        $security_deposit_val = -1;

        if ($result = mysqli_query($link, $sql)) {
            $rowtesbf = mysqli_fetch_assoc($result);
            if (isset($rowtesbf['custom_field']) && ($data_tesbro = json_decode($rowtesbf['custom_field'], true))) {
                if (isset($data_tesbro['vacation_rental']['security_deposit'])) {
                    $security_deposit_val = $data_tesbro['vacation_rental']['security_deposit'];
                }
            }
        }

        // === COSTRUZIONE DELLA QUERY ===

        // VAT FALSE = imponibile
        if ($vat == FALSE) {
            $where = " WHERE r.id_tes = '" . $tesbro . "'";
            $need_artico_join = false;

            if ($tourist_tax == TRUE && $add_extra == FALSE) {
                $where .= " AND (r.codart LIKE 'TASSA-TURISTICA%' OR (a.custom_field REGEXP 'accommodation_type'))";
                $need_artico_join = true;
            } elseif ($add_extra == FALSE && $tourist_tax == FALSE) {
                $where .= " AND (a.custom_field REGEXP 'accommodation_type') AND r.codart NOT LIKE 'TASSA-TURISTICA%'";
                $need_artico_join = true;
            } elseif ($tourist_tax == FALSE && $add_extra == TRUE) {
                $where .= " AND r.codart NOT LIKE 'TASSA-TURISTICA%'";
            }

            $sql = "SELECT SUM(COALESCE(r.quanti, 0) * COALESCE(r.prelis, 0)) AS totalprice FROM " . $tablerig . " r";

            if ($need_artico_join) {
                $sql .= " LEFT JOIN " . $tableart . " a ON a.codice = CASE
                    WHEN r.codart IS NOT NULL AND r.codart != '' THEN r.codart
                    WHEN r.codice_fornitore IS NOT NULL AND r.codice_fornitore != '' THEN r.codice_fornitore
                    ELSE NULL END";
            }

            $sql .= $where;

            if ($result = mysqli_query($link, $sql)) {
                $row = mysqli_fetch_assoc($result);

                // Somma spese bancarie
                $sql = "SELECT speban FROM " . $tabletes . " WHERE id_tes = " . $tesbro . " LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                    $rowtes = mysqli_fetch_assoc($result);
                    $rowtes['speban'] = isset($rowtes['speban']) ? $rowtes['speban'] : 0;
                    $totalprice = $row['totalprice'] + $rowtes['speban'];
                    return $totalprice;
                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($link);
                }
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($link);
            }
        } else {
            // === VAT TRUE = IVA COMPRESA ===
			$totalprice = 0;
            $totalsecdep = 0;

            $where = " WHERE (r.id_tes = '" . $tesbro . "' OR (r.id_tes = '" . $tesbro . "' AND r.prelis < 0))";
            $sql = "SELECT r.quanti, r.prelis, i.aliquo, a.codice
                    FROM " . $tablerig . " r
                    LEFT JOIN " . $tableiva . " i ON i.codice = r.codvat
                    LEFT JOIN " . $tableart . " a ON r.codart = a.codice " . $where;

            if ($result = mysqli_query($link, $sql)) {

                foreach ($result as $res) {
                    $prezzo = ($res['prelis'] * $res['quanti']);
                    $iva = ($prezzo * $res['aliquo']) / 100;
                    $totalprice += $prezzo + $iva;
                    if ($security_deposit == TRUE) {
                        $sql = "SELECT custom_field FROM " . $tableart . " WHERE codice = '" . $res['codice'] . "'";
                        if ($result2 = mysqli_query($link, $sql)) {
                            $row2 = mysqli_fetch_assoc($result2);
                            if (isset($row2['custom_field']) && ($data = json_decode($row2['custom_field'], true))) {
                                if (isset($data['vacation_rental']['accommodation_type'])) {
                                    if ($security_deposit_val == -1) {
                                        if (isset($data['vacation_rental']['security_deposit']) && floatval($data['vacation_rental']['security_deposit']) > 0) {
                                            $totalsecdep += floatval($data['vacation_rental']['security_deposit']);
                                        }
                                    } else {
                                        $totalsecdep += floatval($security_deposit_val);
                                    }
                                }
                            }
                        }
                    }
                }

                // Calcolo spese bancarie con IVA
                if (intval($preeminent_vat) > 0) {
                    $sql = "SELECT aliquo FROM " . $tableiva . " WHERE codice = " . intval($preeminent_vat);
                    if ($result = mysqli_query($link, $sql)) {
                        $row = mysqli_fetch_assoc($result);
                        $spevat = $row['aliquo'];
                    } else {
                        echo "Error: " . $sql . "<br>" . mysqli_error($link);
                    }
                } else {
                    $spevat = 0;
                }

                $sql = "SELECT speban FROM " . $tabletes . " WHERE id_tes = '" . $tesbro . "' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                    $rowtes = mysqli_fetch_assoc($result);
                    $rowtes['speban'] = isset($rowtes['speban']) ? $rowtes['speban'] : 0;
                    $rowtes['speban'] += ($rowtes['speban'] * $spevat) / 100;
                    $totalprice += $rowtes['speban'];
                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($link);
                    die;
                }

                return $totalprice + $totalsecdep;
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($link);
            }
        }
    } else {
        return "tesbro vuoto";
    }
}


function get_total_promemo($startprom,$endprom){// STAT
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tableart = $azTables."artico";
    $tablerent_ev = $azTables."rental_events";
    $tabletes = $azTables."tesbro";
  }else{
    $tableart = $gTables['artico'];
    $tablerent_ev = $gTables['rental_events'];
    $tabletes = $gTables['tesbro'];
  }
  $data = [];
  $tot_nights_booked=0;
  $ret=[];
  $ret['totalprice_booking']=0;
  $what = "codice";
  $datediff = strtotime($endprom)-strtotime($startprom);
  $night_promemo = round($datediff / (60 * 60 * 24));// numero notti dell'arco di tempo richiesto
  $where = "good_or_service=1 AND (custom_field REGEXP 'accommodation_type')";
  $sql = "SELECT ".$what." FROM ".$tableart." WHERE ".$where;
  $resulth = mysqli_query($link, $sql); // prendo tutti gli alloggi
  $num_all = $resulth->num_rows;// numero alloggi presenti in GAzie
  foreach ($resulth as $resh){ // per ogni alloggio
    // prendo tutti gli eventi dell'alloggio che interessano l'arco di tempo richiesto
    $sql = "SELECT * FROM ".$tablerent_ev." LEFT JOIN ".$tabletes." ON  ".$tablerent_ev.".id_tesbro = ".$tabletes.".id_tes WHERE  ".$tablerent_ev.".type = 'ALLOGGIO' AND ".$tablerent_ev.".id_tesbro > 0 AND (custom_field IS NULL OR custom_field LIKE '%PENDING%' OR custom_field LIKE '%CONFIRMED%' OR custom_field LIKE '%FROZEN%') AND house_code='".substr($resh['codice'], 0, 32)."' AND ( start <= '".$endprom."' AND(start >= '".$startprom."' OR start <= '".$endprom."') AND (end >= '".$startprom."' OR end <= '".$endprom."') AND end >= '".$startprom."') ORDER BY id ASC";
    //echo $sql;

    if ($result = @mysqli_query($link, $sql)){

	}else{
		echo "Error: " . $sql . "<br>" . mysqli_error($link);
	}


    foreach($result as $row){ // per ogni evento dell'alloggio
      //echo "<pre>evento alloggio:",print_r($row),"</pre>";
      $datediff = strtotime($row['end'])-strtotime($row['start']);
      $nights_event = round($datediff / (60 * 60 * 24));// numero notti totali della prenotazione(evento)
      $tot_n_event_in_promemo=0;
      $start=$row['start'];
      $end=$row['end'];
      // ciclo i giorni dell'evento
      while (strtotime($start) < strtotime($end)) {// per ogni giorno dell'evento

        if ($start >= $startprom AND $start <= date ("Y-m-d", strtotime("-1 days", strtotime($endprom)))) {// se il giorno è dentro l'arco di tempo richiesto (tolgo una giorno a endprom perché devo conteggiare le notti)
		  //echo "<br>",$start," è dentro";
          if (!isset($data[$start])){
            $data[$start]=array();
          }
            if (!in_array($row['house_code'],$data[$start])){// escludendo i giorni che hanno già quell'alloggio
             array_push($data[$start],$row['house_code']);// conteggio il giorno per questo alloggio
             $tot_nights_booked  ++;
             $tot_n_event_in_promemo ++;
          }

        }
        $start = date ("Y-m-d", strtotime("+1 days", strtotime($start)));// aumento di un giorno il ciclo
      }
      $ret['totalprice_booking'] += ((get_totalprice_booking($row['id_tesbro'],false,false,"",false,false))/$nights_event)*$tot_n_event_in_promemo;// aggiungo il costo medio della locazione(evento) calcolata sui giorni che rientrano nell'arco di tempo richiesto
	  //il prezzo è imponibile e senza tassa turistica
	}
  }
  $ret['tot_nights_bookable']= $num_all * $night_promemo;
  $ret['perc_booked'] = ($ret['tot_nights_bookable']>0)?(($tot_nights_booked/$ret['tot_nights_bookable'])*100):0;
  $ret['tot_nights_booked'] = $tot_nights_booked;

  return $ret;
}

function get_datasets($startprom,$endprom){// STAT graph
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tableart = $azTables."artico";
    $tablerent_ev = $azTables."rental_events";
    $tabletes = $azTables."tesbro";
  }else{
    $tableart = $gTables['artico'];
    $tablerent_ev = $gTables['rental_events'];
    $tabletes = $gTables['tesbro'];
  }
  $data = [];
  $tot_nights_booked=0;
  $ret=[];
  $ret['totalprice_booking']=0;
  $retsumdat=[];
  $what = "codice";
  $datediff = strtotime($endprom)-strtotime($startprom);
  $night_promemo = round($datediff / (60 * 60 * 24));// numero notti dell'arco di tempo richiesto
  $where = "good_or_service=1 AND (custom_field REGEXP 'accommodation_type')";
  $sql = "SELECT ".$what." FROM ".$tableart." WHERE ".$where;
  $resulth = mysqli_query($link, $sql); // prendo tutti gli alloggi
  $num_all = $resulth->num_rows;// numero alloggi presenti in GAzie
  foreach ($resulth as $resh){ // per ogni alloggio
    // prendo tutti gli eventi dell'alloggio che interessano l'arco di tempo richiesto
    $sql = "SELECT * FROM ".$tablerent_ev." LEFT JOIN ".$tabletes." ON  ".$tablerent_ev.".id_tesbro = ".$tabletes.".id_tes WHERE  ".$tablerent_ev.".type = 'ALLOGGIO' AND ".$tablerent_ev.".id_tesbro > 0 AND (custom_field IS NULL OR custom_field LIKE '%PENDING%' OR custom_field LIKE '%CONFIRMED%' OR custom_field LIKE '%FROZEN%') AND house_code='".substr($resh['codice'], 0, 32)."' AND ( start <= '".$endprom."' AND(start >= '".$startprom."' OR start <= '".$endprom."') AND (end >= '".$startprom."' OR end <= '".$endprom."') AND end >= '".$startprom."') ORDER BY id ASC";
    //echo $sql;
    $result = mysqli_query($link, $sql);
	if ($result->num_rows>0){

		foreach($result as $row){ // per ogni evento dell'alloggio
		  //echo "<pre>evento alloggio:",print_r($row),"</pre>";
		  $datediff = strtotime($row['end'])-strtotime($row['start']);
		  $nights_event = round($datediff / (60 * 60 * 24));// numero notti totali della prenotazione(evento)
		  $tot_n_event_in_promemo=0;
		  $start=$row['start'];
		  $end=$row['end'];
		  while (strtotime($start) < strtotime($end)) { // per ogni giorno dell'evento
			$week = date("W", strtotime($start));   // numero settimana ISO 01-53
			$year = date("Y", strtotime($start));

			if ($start >= $startprom && $start <= date("Y-m-d", strtotime("-1 days", strtotime($endprom)))) {

				// IMPORTI
				$house = substr($resh['codice'], 0, 32);
				if (!isset($retsumdat['IMPORTI'][$year][$house][$week])) {
					$retsumdat['IMPORTI'][$year][$house][$week] = 0;
				}
				if (!isset($retsumdat['IMPORTI'][$year]['TUTTI'][$week])) {
					$retsumdat['IMPORTI'][$year]['TUTTI'][$week] = 0;
				}
				$value = get_totalprice_booking($row['id_tesbro'], FALSE) / $nights_event;
				$retsumdat['IMPORTI'][$year][$house][$week] += $value;
				$retsumdat['IMPORTI'][$year]['TUTTI'][$week] += $value;

				// OCCUPAZIONE
				if (!isset($data[$start])) $data[$start] = array();
				if (!in_array($row['house_code'], $data[$start])) {
					array_push($data[$start], $row['house_code']);

					if (!isset($retsumdat['OCCUPAZIONE'][$year][$house . '-occupazione'][$week])) {
						$retsumdat['OCCUPAZIONE'][$year][$house . '-occupazione'][$week] = 0;
					}
					$retsumdat['OCCUPAZIONE'][$year][$house . '-occupazione'][$week]++;

					if (!isset($retsumdat['OCCUPAZIONE'][$year]['occup. tutti'][$week])) {
						$retsumdat['OCCUPAZIONE'][$year]['occup. tutti'][$week] = 0;
					}
					$retsumdat['OCCUPAZIONE'][$year]['occup. tutti'][$week]++;

					$tot_nights_booked++;
					$tot_n_event_in_promemo++;
				}

			}

			$start = date("Y-m-d", strtotime("+1 days", strtotime($start))); // aumento di un giorno
		}

		  $ret['totalprice_booking'] += ((get_totalprice_booking($row['id_tesbro'],false,false,"",false,false))/$nights_event)*$tot_n_event_in_promemo;// aggiungo il costo medio della locazione(evento) calcolata sui giorni che rientrano nell'arco di tempo richiesto
		  //il prezzo è imponibile e senza tassa turistica
		}
		//echo "<br><b>tot book:",$ret['totalprice_booking']," - night prenotaz:",$nights_event," - total preno:",get_totalprice_booking($row['id_tesbro'],false,false,"",false,false),"</b>";
	}
  }
  $ret['tot_nights_bookable']= $num_all * $night_promemo;
  $ret['perc_booked'] = ($ret['tot_nights_bookable']>0)?(($tot_nights_booked/$ret['tot_nights_bookable'])*100):0;
  $ret['tot_nights_booked'] = $tot_nights_booked;
  // Costruisco dataset come ARRAY PHP vero
	$dataret = [];

	// Funzione helper: ritorna timestamp del lunedì della settimana ISO
	function week_start_timestamp($year, $week) {
		$dto = new DateTime();
		$dto->setISODate($year, $week);
		$dto->setTime(0,0,0); // inizio giorno
		return $dto->getTimestamp(); // in secondi
	}

	// --- IMPORTI ---
	if (isset($retsumdat['IMPORTI'])) {
		foreach ($retsumdat['IMPORTI'] as $year => $structures) {
			foreach ($structures as $name => $weeks) {
				$label = $year . '-' . $name;
				$dataret['IMPORTI'][$label]['label'] = $label;
				$dataret['IMPORTI'][$label]['data'] = [];
				foreach ($weeks as $week => $value) {
					$ts = week_start_timestamp($year, $week) * 1000; // -> millisecondi
					$dataret['IMPORTI'][$label]['data'][] = [$week, round((float)$value, 2)]; // 1,2,3...
				}
				// ordina per X (timestamp) crescente
				usort($dataret['IMPORTI'][$label]['data'], fn($a,$b) => $a[0]-$b[0]);
			}
		}
	}

	// --- OCCUPAZIONE ---
	if (isset($retsumdat['OCCUPAZIONE'])) {
    foreach ($retsumdat['OCCUPAZIONE'] as $year => $structures) {
        foreach ($structures as $name => $weeks) {
            $label = $year . '-' . $name;
            $dataret['OCCUPAZIONE'][$label]['label'] = $label;
            $dataret['OCCUPAZIONE'][$label]['data'] = [];

            foreach ($weeks as $week => $value) {
                // timestamp del lunedì della settimana
                $ts = week_start_timestamp($year, $week) * 1000;

                $occup_days = (float)$value; // giorni occupati in quella settimana

                // Se è "occup. tutti", divido per num_all*7, altrimenti per 7 giorni
                if ($name === 'occup. tutti') {
                    $percent = ($occup_days / ($num_all * 7)) * 100;
                } else {
                    $percent = ($occup_days / 7) * 100; // singolo appartamento
                }
				$percent = round($percent, 2); // arrotonda a massimo 2 decimali
                $dataret['OCCUPAZIONE'][$label]['data'][] = [$week, $percent];
            }

            // ordina per settimana
            usort($dataret['OCCUPAZIONE'][$label]['data'], fn($a,$b) => $a[0]-$b[0]);
        }
    }
}


	return $dataret;


}

function get_next_check($startprom,$endprom){
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  $next['in']=[];
  $next['out']=[];
  if ($azTables){
    $tableart = $azTables."artico";
    $tablerent_ev = $azTables."rental_events". " LEFT JOIN " . $azTables['tesbro'] . " ON " .$azTables['tesbro'] . ".id_tes = " . $azTables['rental_events'] . ".id_tesbro";
    $tabletes = $azTables."tesbro";
  }else{
    $tableart = $gTables['artico'];
    $tablerent_ev = $gTables['rental_events']. " LEFT JOIN " . $gTables['tesbro'] . " ON " .$gTables['tesbro'] . ".id_tes = " . $gTables['rental_events'] . ".id_tesbro";
    $tabletes = $gTables['tesbro'];
  }
  $rs_booking = gaz_dbi_dyn_query("id,start,end", $tablerent_ev, "(start >= ".$startprom." OR start <= ".$endprom." OR end >= ".$startprom." OR end <= ".$endprom.")  AND type = 'ALLOGGIO' AND ".$tabletes.".custom_field LIKE '%CONFIRMED%'", "id asc");
  while ($booking = gaz_dbi_fetch_assoc($rs_booking)){// ciclo le prenotazioni che interessano arco di tempo richiesto
    if (intval($booking['id'])>0 && $booking['start']>= date ("Y-m-d", strtotime("-3 days", strtotime($startprom))) && $booking['start'] <= $endprom){//se la data di check-in è dentro ( prendo anche eventuali checkin ritardatari fino a 3 giorni
      $next['in'][]=$booking;
    }
    if (intval($booking['id'])>0 && $booking['end']>= $startprom && $booking['end'] <= $endprom){//se la data di check-out è dentro
	  $next['out'][]=$booking;
    }
  }
  return $next;
}

function get_total_paid($idtesbro){// totale pagato nella locazione
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tablerent_pay = $azTables."rental_payments";
  }else{
    $tablerent_pay = $gTables['rental_payments'];
  }
  $where = " WHERE id_tesbro = '".$idtesbro."' AND payment_status = 'Completed' AND type <> 'Deposito_cauzionale'";
  $sql = "SELECT SUM(payment_gross) AS totalpaid FROM ".$tablerent_pay.$where;
  if ($result = mysqli_query($link, $sql)) {
    $row = mysqli_fetch_assoc($result);

    return $row['totalpaid'];
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}
function get_secdep_paid($idtesbro){// totale deposito cauzionale pagato per la locazione
  global $link, $azTables, $gTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $tablerent_pay = $azTables."rental_payments";
  }else{
    $tablerent_pay = $gTables['rental_payments'];
  }
  $where = " WHERE id_tesbro = '".$idtesbro."' AND payment_status = 'Completed' AND type = 'Deposito_cauzionale'";
  $sql = "SELECT SUM(payment_gross) as totalpaid FROM ".$tablerent_pay.$where;

  if ($result = mysqli_query($link, $sql)) {
    $row = mysqli_fetch_assoc($result);

    return $row['totalpaid'];
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

function get_user_points_level($id_anagra, $point=false){// determina il livello punti raggiunto dal cliente. Restituisce null se il sistema punti è disabilitato o non correttamente impostato. Restituisce array con livello e punti se point=true
  global $link, $azTables, $gTables, $genTables;// posso chiamare la funzione con entrambi i metodi
  if ($azTables){
    $table = $genTables."anagra";
  }else{
    $table = $gTables['anagra'];
  }
  $where = " WHERE id = '".$id_anagra."'";
  $sql = "SELECT custom_field FROM ".$table.$where;
  if ($result = mysqli_query($link, $sql)) {// prendo il customfield in anagra
    $row = mysqli_fetch_assoc($result);
    $user_point=0;
    if (isset($row['custom_field']) && ($data = json_decode($row['custom_field'],true))){// se c'è un json in anagra
      if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
        if (isset($data['vacation_rental']['points'])){
          $user_point = intval($data['vacation_rental']['points']);
        }
      }
    }
    if ($azTables){
      $table = $azTables."company_config";
    }else{
      $table = $gTables['company_config'];
    }
	$user_lev=null;
    $sql = "SELECT * FROM ". $table ." WHERE var = 'pointenable' ORDER BY id ASC LIMIT 1";
    if ($result = mysqli_query($link, $sql)) {// prendo il customfield in anagra
      $row = mysqli_fetch_assoc($result);
      $pointenable=$row['val'];
    }

    $sql = "SELECT * FROM ". $table ." WHERE var LIKE 'pointlevel%' ORDER BY id ASC";
    if ($result = mysqli_query($link, $sql)) {// prendo i livelli dalle impostazioni generali
      $levname="";
      if (intval($pointenable)>0 ){
        while ($rigpoint = mysqli_fetch_array($result)){
          if (substr($rigpoint['description'],0,12)=="Nome livello"){
            $lev_number=substr($rigpoint['description'],13);
          }
          if (substr($rigpoint['description'],0,13)=="Punti livello"){
            if ($user_point>=$rigpoint['val']){
              $user_lev=$lev_number;
            }
          }
        }
      }
	  if ($point == false){
		return $user_lev;// restituisco il numero del livello
	  }else{
		  $ret['user_level']=$user_lev;
		  $ret['user_point']=$user_point;
		  return $ret;
	  }
    }else {
       echo "Error: " . $sql . "<br>" . mysqli_error($link);
    }
  }else {
     echo "Error: " . $sql . "<br>" . mysqli_error($link);
  }
}

function check_availability($start, $end, $house_code, $open_from = "", $open_to = "") {
    global $link, $azTables, $gTables;

    static $house_cache = []; // caching dei custom_field artico_group
    static $availability_cache = []; // memoization per stesso periodo

    $cache_key = $house_code . "|" . $start . "|" . $end . "|" . $open_from . "|" . $open_to;
    if (isset($availability_cache[$cache_key])) {
        return $availability_cache[$cache_key];
    }

    $unavailable = 1; // default disponibile
    $check_in = $start;

    // tabelle
    if ($azTables) {
        $table = $azTables . "rental_events";
        $table_ts = $azTables . "tesbro";
        $table_gr = $azTables . "artico_group";
        $table_ar = $azTables . "artico";
    } else {
        $table = $gTables['rental_events'];
        $table_ts = $gTables['tesbro'];
        $table_gr = $gTables['artico_group'];
        $table_ar = $gTables['artico'];
    }

    $start_ts = strtotime($start);
    $end_ts = strtotime($end);

    // --- 1) controllo apertura
    $check_open = true;
    if (intval($open_from) > 0) {
        $open_from_ts = strtotime($open_from . "-" . date("Y", $start_ts));
        $open_to_ts = strtotime($open_to . "-" . date("Y", $start_ts));
        if ($start_ts < $open_from_ts || $start_ts > $open_to_ts) {
            $check_open = false;
        }
    }

    if ($check_open) {
        // --- 2) batch query eventi
        $sql = "SELECT start, end, custom_field
                FROM $table
                LEFT JOIN $table_ts ON $table.id_tesbro = $table_ts.id_tes
                WHERE house_code='" . mysqli_real_escape_string($link, $house_code) . "'
                AND ((start <= '$end' AND end > '$start'))
                AND (custom_field IS NULL
                    OR custom_field LIKE '%PENDING%'
                    OR custom_field LIKE '%CONFIRMED%'
                    OR custom_field LIKE '%FROZEN%'
                    OR custom_field LIKE '%ISSUE%')";
        $res = mysqli_query($link, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $event_start = strtotime($row['start']);
                $event_end = strtotime($row['end']);
                if ($start_ts < $event_end && $end_ts > $event_start) {
                    $availability_cache[$cache_key] = 0;
                    return 0; // NON disponibile
                }
            }
        } else {
            echo "Error: $sql<br>" . mysqli_error($link);
        }
    } else {
        $availability_cache[$cache_key] = 0;
        return 0; // NON disponibile perché chiuso
    }

    // --- 3) controllo limitazioni settimanali (artico_group)
    if (!isset($house_cache[$house_code])) {
        $sql = "SELECT $table_gr.custom_field
                FROM $table_gr
                LEFT JOIN $table_ar ON $table_gr.id_artico_group = $table_ar.id_artico_group
                WHERE $table_ar.codice='" . mysqli_real_escape_string($link, $house_code) . "'";
        $res = mysqli_query($link, $sql);
        if ($res) {
            $row = mysqli_fetch_assoc($res);
            $house_cache[$house_code] = isset($row['custom_field']) ? json_decode($row['custom_field'], true) : [];
        } else {
            echo "Error: $sql<br>" . mysqli_error($link);
            $house_cache[$house_code] = [];
        }
    }

    $data = $house_cache[$house_code];
    if (isset($data['vacation_rental'])) {
        // check-in settimanale
        if (!empty($data['vacation_rental']['week_check_in'])) {
            $in_days = array_map('intval', explode(",", $data['vacation_rental']['week_check_in']));
            if (!in_array(date('w', $start_ts), $in_days)) {
                $availability_cache[$cache_key] = 0;
                return 0;
            }
        }
        // check-out settimanale
        if (!empty($data['vacation_rental']['week_check_out'])) {
            $out_days = array_map('intval', explode(",", $data['vacation_rental']['week_check_out']));
            if (!in_array(date('w', $end_ts), $out_days)) {
                $availability_cache[$cache_key] = 0;
                return 0;
            }
        }
    }

    // --- 4) tutto ok, disponibile
    $availability_cache[$cache_key] = 1;
    return 1;
}


/**
 * Restituisce la quantità residua disponibile di BED per un alloggio/facility in un determinato periodo
 *
 * @param string $house_code Codice dell'alloggio
 * @param string $facility_group ID del gruppo/facility
 * @param string $start Data inizio prenotazione (YYYY-MM-DD)
 * @param string $end Data fine prenotazione (YYYY-MM-DD)
 * @return int Quantità residua disponibile di BED
 */
function getAvailableExtraBeds($link, $azTables, $house_code, $facility_id, $start, $end) {
    // 1. controllo se esiste un BED extra dalla tabella rental_extra/artico
    $sql = "SELECT re.codart, re.max_quantity
            FROM ".$azTables."rental_extra re
            LEFT JOIN ".$azTables."artico a
                ON re.codart = a.codice
            WHERE (
                    (re.rif_alloggio = '".$house_code."' OR re.rif_alloggio IS NULL OR re.rif_alloggio = '')
                 AND (FIND_IN_SET('".$facility_id."', re.rif_facility) OR re.rif_facility IS NULL OR re.rif_facility = '')
                 )
              AND a.quality = 'BED'
              AND (a.ordinabile IS NULL OR a.ordinabile = '' OR a.ordinabile = 'S')
            LIMIT 1";
  //echo $sql,"<br>";
    if ($result = mysqli_query($link, $sql)) {
        if ($row_beds = mysqli_fetch_assoc($result)) {
  //print_r($row_beds);
            $num_beds_toAdd = isset($row_beds['max_quantity']) ? intval($row_beds['max_quantity']) : 1;

            // Caso max_quantity = 0 → senza limiti, restituisco 10 direttamente (numero forfettario)
            if ($num_beds_toAdd === 0) {
                return 10;
            }

            // 2. Calcolo quanti BED sono già prenotati nel periodo
            $beds_occupied = 0;
            $sql_occupied = "SELECT IFNULL(SUM(rb.quanti),0) AS occupied
                             FROM ".$azTables."rigbro rb
                             INNER JOIN ".$azTables."rental_events re
                                ON re.id_rigbro = rb.id_rig
                             INNER JOIN ".$azTables."tesbro t
                                ON t.id_tes = rb.id_tes
                             WHERE rb.codart = '".$row_beds['codart']."'
                               AND re.start <= '".$end."'
                               AND re.end >= '".$start."'
                               AND JSON_UNQUOTE(JSON_EXTRACT(t.custom_field, '$.vacation_rental.status'))
                                   IN ('PENDING','CONFIRMED','FROZEN')";
  //echo "<br>",$sql_occupied,"<br>";
            if ($res_occ = mysqli_query($link, $sql_occupied)) {
                $row_occ = mysqli_fetch_assoc($res_occ);
  // print_r($row_occ);
                $beds_occupied = intval($row_occ['occupied']);
            }

            // 3. Calcolo quantità residua disponibile
            $available_beds = max(0, $num_beds_toAdd - $beds_occupied);
            return $available_beds;
        }
    }

    // 4. Nessun BED trovato → restituisco 0
    return 0;
}




use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Ddeboer\Imap\Server;
function set_mailer() {
  global $gTables;  // Accedi alla variabile globale gTables

  // Impostazioni per PHPMailer
  $host = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_server')['val'];
  $usr = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_user')['val'];

  $rsdec = gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val), '".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'smtp_password'");
  $rdec = gaz_dbi_fetch_row($rsdec);
  $psw = $rdec ? $rdec[0] : '';

  $port = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_port')['val'];

  // Imposta l'oggetto PHPMailer
  $mail = new PHPMailer(true);
  $mail->CharSet = 'UTF-8';
  $mail->isSMTP();  // Usa SMTP
  $mail->Host = $host;  // Server SMTP
  $mail->SMTPAuth = true;  // Abilita l'autenticazione SMTP
  $mail->Username = $usr;  // Nome utente SMTP
  $mail->Password = $psw;  // Password SMTP
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // TLS/SSL
  $mail->Port = $port;  // Porta SMTP
  $mail->Timeout = 10;  // Timeout in secondi

  return $mail;  // Restituisce l'oggetto PHPMailer
}

function set_imap($id_anagra){// restituisce le impostazioni imap in un array
  global $genTables,$azTables,$link,$IDaz;
  include("./manual_settings.php");
  if (intval($id_anagra)>0){
    $sql = "SELECT ".$genTables."anagra.custom_field, codice FROM ".$genTables."anagra"." LEFT JOIN ".$azTables."clfoco"." ON ".$azTables."clfoco".".id_anagra = ".$id_anagra." WHERE id = ".$id_anagra." AND codice LIKE '2%' LIMIT 1";
    if ($result = mysqli_query($link, $sql)) { // prendo il custom field del proprietario
      $anagra = mysqli_fetch_assoc($result);
      $custom_field=(isset($anagra['custom_field']))?$anagra['custom_field']:'';
    }else {
       echo "Error: " . $sql . "<br>" . mysqli_error($link);
    }
    if (isset($custom_field) && $data = json_decode($custom_field,true)){// se c'è un json e c'è una mail aziendale utente
      $imap=[]; // imap_pwr me la devo prendere per forza dal manul setting perché la decriptazione di quella di GAzie usa $_SESSION['aes_key'] e qui non ce l'ho
      if (isset($data['config']) && isset($data['config'][$IDaz])){ // se c'è il modulo "config" e c'è l'azienda attuale posso procedere
        $imap['imap_usr']=$data['config'][$IDaz]['imap_usr'];
        $imap['imap_sent_folder']=$data['config'][$IDaz]['imap_sent_folder'];
        $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_server' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
          $imap['imap_server']=$val['val'];
        }
        $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_port' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
          $imap['imap_port']=$val['val'];
        }
        $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_secure' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
          $imap['imap_secure']=$val['val'];
        }
        return $imap;
      } else{// provo a vedere se è connesso con un utente amministratore

        $sql = "SELECT adminid FROM ".$azTables."agenti"." WHERE id_fornitore = '".$anagra['codice']."' LIMIT 1";
        if ($result = mysqli_query($link, $sql)) {
          $val = mysqli_fetch_assoc($result);
         if (isset($val) && $val['adminid'] !== "no_user"){// se il proprietario è connesso con un utente admin
          $sql = "SELECT id_anagra FROM ".$genTables."admin"." WHERE user_name = '".$val['adminid']."' LIMIT 1";
          if ($result = mysqli_query($link, $sql)) {
            $val = mysqli_fetch_assoc($result);
            $sql = "SELECT custom_field FROM ".$genTables."anagra"." WHERE id = '".$val['id_anagra']."' LIMIT 1";
            if ($result = mysqli_query($link, $sql)) {
              $anagra = mysqli_fetch_assoc($result);
              $custom_field=$anagra['custom_field'];
              if ($data = json_decode($custom_field,true)){// se c'è un json
              if (isset($data['config']) && isset($data['config'][$IDaz])){ // se c'è il modulo "config" e c'è l'azienda attuale posso procedere
                $imap['imap_usr']=$data['config'][$IDaz]['imap_usr'];
                $imap['imap_sent_folder']=$data['config'][$IDaz]['imap_sent_folder'];
                $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_server' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $imap['imap_server']=$val['val'];
                }
                $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_port' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $imap['imap_port']=$val['val'];
                }
                $sql = "SELECT val FROM ".$azTables."company_config"." WHERE var = 'imap_secure' LIMIT 1";
                if ($result = mysqli_query($link, $sql)) {
                  $val = mysqli_fetch_assoc($result);
                  $imap['imap_secure']=$val['val'];
                }
                $imap['imap_pwr']=$imap_pwr;
                return $imap;
              }
              }
            }
          }
         }
        }
      }
    }
  }
  return false;
}

// Calcolo prezzo con sconti e controllo la prenotabilità in base min stay giornaliero del prezzo
function get_price_bookable($start,$end,$housecode,$aliquo,$ivac,$web_price,$web_url,$descri,$lang,$in_fixquote,$id_artico_group){
	;
  global $genTables,$azTables,$link,$IDaz,$script_transl,$admin_aziend;
  $minstay_memo=0;
  $accommodations=array();
  $datediff = strtotime($end)-strtotime($start);
	$nights=round($datediff / (60 * 60 * 24));
	;
  $accommodations['msg']=[];
  $accommodations['id_artico_group']=$id_artico_group;
  $accommodations['price']=0;
  $accommodations['codice']=$housecode;
 
  $accommodations['descri']=$descri;
  $accommodations['web_url']=get_string_lang($web_url, $lang);// se ci sono i tag lingua restituisco l'url nella lingua appropriata
  $accommodations['aliquo']=$aliquo;
  $startw=$start;
 // Recupero tutti i prezzi del periodo con UNA SOLA query
  $prezzi_periodo = [];

  $housecode_safe = mysqli_real_escape_string($link,$housecode);

  $sql = "SELECT start, end, price, minstay
          FROM ".$azTables."rental_prices
          WHERE house_code = '".$housecode_safe."'
          AND start <= '".$end."'
          AND end >= '".$start."'";

  if ($result = mysqli_query($link,$sql)){
    while($row = mysqli_fetch_assoc($result)){
      $prezzi_periodo[] = $row;
    }
  }

  while (strtotime($startw) < strtotime($end)) {// ciclo il periodo della locazione richiesta giorno per giorno

    $prezzo = null;

    // cerco il prezzo valido per il giorno corrente tra quelli caricati
    foreach($prezzi_periodo as $p){
      if ($p['start'] <= $startw && $p['end'] >= $startw){
        $prezzo = $p;
        break;
      }
    }

    if (isset($prezzo['minstay']) && intval($prezzo['minstay'])>0 && intval($nights) < intval($prezzo['minstay'])){// se richiesto controllo se non si è raggiunto il soggiorno minimo giornaliero del prezzo
      if (intval($prezzo['minstay'])>$minstay_memo){
        $minstay_memo=intval($prezzo['minstay']);
        $accommodations['msg'][]=$script_transl['msg_minstay']." ".$prezzo['minstay']." ".$script_transl['nights'];
      }
    }

    // NB: il prezzo mostrato al pubblico deve essere sempre IVA compresa
    if (isset($prezzo)){// se c'è un prezzo nel calendario lo uso
      if ($ivac=="si"){
        $accommodations['price'] += floatval($prezzo['price']);
      }else{
        $accommodations['price'] += floatval($prezzo['price'])+((floatval($prezzo['price'])*floatval($aliquo))/100);
      }

    } elseif(floatval($web_price)>0){// altrimenti uso il prezzo base al quale devo sempre aggiungere l'iva

      $accommodations['price'] += floatval($web_price)+((floatval($web_price)*floatval($aliquo))/100);

    }else{// se non c'è alcun prezzo non posso prenotare e metto non prenotabile

      unset ($accommodations);
      return false;

    }

	// ✅ incremento corretto di un giorno
    $startw = date("Y-m-d", strtotime($startw . " +1 day"));  
}
  // Se ho trovato prezzo disponibile procedo con il calcolo sconti
  $accommodations['fixquote'] = floatval($in_fixquote)+((floatval($in_fixquote)*floatval($aliquo))/100);// inizializzo eventuale quota fissa e aggiungo IVA
  $accommodations['price'] += $accommodations['fixquote'];

  // calcolo gli sconti
  $discounts=searchdiscount($housecode,$id_artico_group,$start,$end,$nights,$anagra=0);
  

  
  $accommodations['discount']=0;
  $accommodations['descri_discount']="";

  $today=date('Y-m-d');
  if (isset($discounts) && $discounts->num_rows >0){// se c'è almeno uno sconto
    foreach ($discounts as $discount){// li ciclo e applico lo sconto
	
	
      // controllo se riservato ad APP
		// error_log("Applying discount: ".$discount['title']." priority=".$discount['priority']." stop=".$discount['stop_further_processing']);      if (intval($discount['device_disc']) == 1 && $discount['app_name']){
		 //error_log("Sono entrato per controllare device! ");

        if (!empty($discount['app_name']) && detect_device($discount['app_name']) <> 'app'){
			//error_log("L'app non ha il nome richiesto: " . $discount['app_name']);
			continue;
        }else{
			//error_log("OK L'app HA il nome richiesto: " . $discount['app_name']);
		}

       // controllo desktop solo se device_disc = 2
      if (intval($discount['device_disc']) == 2 && detect_device("") == 'app') {
		  //error_log("Atteso desktop ma siamo in app ");
          continue;
      }

      if (intval($discount['last_min'])>0){// se è un lastmin controllo la validità
        $date=date_create($today);
        date_add($date,date_interval_create_from_date_string($discount['last_min']." days"));
        $time=strtotime(date_format($date,"Y-m-d"));
        if ($time < strtotime($start)){
          continue; // non è valido, continuo con l'eventuale prossimo sconto

        }
      }
      if (intval($discount['level_points'])==0){// escludo gli eventuali sconto livello punti perché non ho ancora il cliente
        if ($accommodations['discount']>0){
          $accommodations['descri_discount'].="+";
        }
        if ($discount['is_percent']==1){
          $accommodations['discount']+= ((floatval($accommodations['price'])-$accommodations['discount'])*floatval($discount['value']))/100;// aggiungo al totale sconti, lo sconto calcolato in percentuale
          $accommodations['descri_discount'].=$discount['title']." ".$discount['value']."%";// incremento la descrizione con lo sconto applicato
        }else{
          $accommodations['discount']+= floatval($discount['value']);// aggiungo al totale sconti, lo sconto a valore
          $accommodations['descri_discount'].= $discount['title']." ".$admin_aziend['symbol']." ".$discount['value'];/// incremento la descrizione con lo sconto applicato

        }
        if ($discount['stop_further_processing']==1){// se questo deve bloccare i successivi eventuali, interrompo il conteggio
          
		
		  
		  break;
        }
      }
    }
  }

  
  return $accommodations;
}

function delete_id_cards($tesbro) {
    // Percorso della cartella principale
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'self_checkin' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $tesbro;
    $keepFile = 'data.json';

    // Se la cartella dei documenti esiste
    if (is_dir($directory)) {
        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && $file !== $keepFile) {
                @unlink($filePath); // @ sopprime eventuali warning
            }
        }
    }

    // Cerca la cartella selfie (es. self_12345_xyz)
    $pattern = __DIR__ . DIRECTORY_SEPARATOR . 'self_checkin' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'self_' . $tesbro . '*';
    $matchingFolders = glob($pattern, GLOB_ONLYDIR);
    // Se esiste almeno una cartella che corrisponde al pattern, cancellala
    if (!empty($matchingFolders)) {
        $selfieDir = $matchingFolders[0]; // Ne esiste solo una, per forza in quanto tesbro è univoco
        $files = scandir($selfieDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $filePath = $selfieDir . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
        @rmdir($selfieDir); // Cancella la cartella una volta svuotata
    }
}

function getUserIpAddress() {// ottengo indirizzo IP utente (CDN cloudflare compatibile)
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Potrebbe contenere una lista separata da virgole
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function isIpBanned($ip) {

	/* ****  IMPORTANTE  *****
	creare il file .htaccess, se non esiste, nella cartella dove si trova banned_ips.txt
	con questo:

	<Files "banned_ips.txt">
		Order allow,deny
		Deny from all
	</Files>

	Serve per evitare che hacker possano leggere o peggio riscrivere il file
	*/

    $file = 'banned_ips.txt';
    if (!file_exists($file)) {
        // Creo il file con permessi sicuri se non esiste
        file_put_contents($file, '', LOCK_EX);
        chmod($file, 0666);
        return false;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $stillBanned = [];
    $now = time();
    $isBanned = false;// NON è bannato

    foreach ($lines as $line) {
        list($bannedIp, $expiry) = explode('|', $line);
        if ($now < (int)$expiry) {
            $stillBanned[] = $line;
            if ($ip === $bannedIp) {
                $isBanned = true; // E' bannato
            }
        }
    }

    // Riscrive solo gli IP ancora validi
    file_put_contents($file, implode(PHP_EOL, $stillBanned) . PHP_EOL, LOCK_EX);
    chmod($file, 0666); // Assicura permessi corretti
    return $isBanned;
}

function banIp($ip, $durationMinutes = 60) {
    $file = 'banned_ips.txt';
    $bannedUntil = time() + ($durationMinutes * 60);
    $entries = [];

    // Gestione casi speciali di IP
    if ($ip === '127.0.0.1' || $ip === '::1') {
        $entries = [
            '127.0.0.1|' . $bannedUntil,
            '::1|' . $bannedUntil
        ];
    } elseif (preg_match('/^::ffff:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $ip, $matches)) {
        $ipv4 = $matches[1];
        $entries = [
            $ip . '|' . $bannedUntil,
            $ipv4 . '|' . $bannedUntil
        ];
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $entries = [$ip . '|' . $bannedUntil];
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $entries = [$ip . '|' . $bannedUntil];
        if (str_starts_with($ip, '::ffff:')) {
            $ipv4 = substr($ip, 7);
            if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $entries[] = $ipv4 . '|' . $bannedUntil;
            }
        }
    }
    // Assicura che il file esista e abbia permessi sicuri
    if (!file_exists($file)) {
        file_put_contents($file, '', LOCK_EX);
        chmod($file, 0666);
    }
    // Scrive le nuove entry
    foreach ($entries as $entry) {
        file_put_contents($file, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    chmod($file, 0666); // Mantiene permessi corretti
}



//CANCELLA UNA PRENOTAZIONE totalmente con tutti i suoi annessi
function delete_booking(int $id_tes, array $admin_aziend, array $gTables) {
/****  DA FARE  NON USARE - NON FUNZIONA    ******/
    //procedo all'eliminazione della testata e dei righi...
    $tesbro = gaz_dbi_get_row($gTables['tesbro'], "id_tes", $id_tes);// la testata che andrò ad eliminare
    //cancello la testata
    gaz_dbi_del_row($gTables['tesbro'], "id_tes", $id_tes);
    //... e i righi
    $rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes =".$id_tes,"id_tes DESC");
    while ($a_row = gaz_dbi_fetch_array($rs_righidel)) { // *** nota bene ***  la cancellazione dei documenti pdf va portata fuori dal ciclo altrimenti prova a cancellare per ogni rigo!
        gaz_dbi_del_row($gTables['rigbro'], "id_rig", $a_row['id_rig']);

        gaz_dbi_del_row($gTables['body_text'], "table_name_ref = 'rigbro' AND id_ref ",$a_row['id_rig']);
    }

    // cancello anche l'evento
    $rental_events = gaz_dbi_get_row($gTables['rental_events'], "id_tesbro", $id_tes);
    gaz_dbi_del_row($gTables['rental_events'], "id_tesbro", $id_tes);

    // aggiorno buono sconto se c'è
    if (isset($rental_events['voucher_id']) && intval($rental_events['voucher_id'])>0){// se era stato usato un buono sconto
        $rental_discounts  = gaz_dbi_get_row($gTables['rental_discounts'], "id", intval($rental_events['voucher_id']));
        if ($rental_discounts['reusable']>0 AND $rental_discounts['STATUS']=="CLOSED"){// se lo sconto era stato chiuso
            $sql = "UPDATE ".$gTables['rental_discounts']." SET STATUS = 'CREATED' WHERE id = ".intval($rental_events['voucher_id']);
            $result = gaz_dbi_query($sql);// riapro lo sconto
        }
    }

    // cancello anche tutti i pagamenti relativi
    gaz_dbi_del_row($gTables['rental_payments'], "id_tesbro", $id_tes);

    // vedo se la prenotazione proveniva da un preventivo
    $prev = gaz_dbi_get_row($gTables['tesbro'], "numfat", $id_tes, " AND datfat = '".$tesbro['datemi']."' AND tipdoc = 'VPR'");
    if ($prev){// se c'è il preventivo lo svincolo
        if ($data = json_decode($prev['custom_field'],true)){// se c'è un json in anagra
            if (is_array($data['vacation_rental'])){ // se c'è il modulo "vacation rental" lo aggiorno
                $data['vacation_rental']['id_booking']='';
                $custom_field = json_encode($data);
            }
        }
        $sql = "UPDATE ".$gTables['tesbro']." SET custom_field = '".$custom_field."', datfat = '0000-00-00', numfat = '0' WHERE id_tes = ".intval($prev['id_tes']);
        $result = gaz_dbi_query($sql);// resetto il preventivo
    }

    // Cancello i PDF della prenotazione e del contratto
    $file = DATA_DIR . "files/" . $admin_aziend['codice'] . "/pdf_Lease/" . $id_tes . ".pdf";
    if (file_exists($file)) {
        if (is_writable(dirname($file))) {
            if (!unlink($file)) {
                // Se volessi log, qui si potrebbe registrare
            }
        }
    }

    // cancellazione multipla addendum
    $dir = dirname(__DIR__) . '/vacation_rental/files/' . $admin_aziend['codice'] . '/pdf_Lease/';
    $pattern = $dir . $id_tes . '*.*';

    if (is_dir($dir)) {
        $files = glob($pattern) ?: [];
        $errors = [];
        foreach ($files as $f) {
            if (is_file($f) && !unlink($f)) $errors[] = $f;
        }
        if (!empty($errors)) {
            // gestione errori, se serve log
        }
    }

    // Cancello anche gli eventuali addendum
    $codice = $admin_aziend['codice'] ?? '';
    $targetDir =  dirname(__DIR__) . "/vacation_rental/files/" . $codice . "/addendum_pdf/" . $id_tes;

    if (is_dir($targetDir)) {
        // Funzione di cancellazione ricorsiva con log errori
        $errors = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                if (!rmdir($path)) $errors[] = "Impossibile cancellare directory: $path";
            } else {
                if (!unlink($path)) $errors[] = "Impossibile cancellare file: $path";
            }
        }
        if (!rmdir($targetDir)) {
            $errors[] = "Impossibile cancellare la directory principale: $targetDir";
        }
    }
}

?>
