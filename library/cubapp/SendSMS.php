<?php
require_once(dirname(__FILE__).'/configCubAPP.php');
require_once(dirname(__FILE__).'/Cubapp.php');

class SendSMS extends CubAPP
{
	public function validate_mobile_number($telephone)
	{
		$filtered_mobile_number = filter_var($telephone, FILTER_SANITIZE_NUMBER_INT);
		$telephone_to_check = str_replace(array('+39','-','+','.'), array('','',''), $filtered_mobile_number);
		if (strlen($telephone_to_check) > 8 && strlen($telephone_to_check) < 11) {
			return $telephone_to_check;
		}
		return false;
	}

	public function runInviaSMS($cellulare, $messaggio)
	{
		return $this->inviaSMS($cellulare, $messaggio);
	}

}

?>