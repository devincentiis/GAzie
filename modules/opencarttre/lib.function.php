<?php
/*
  --------------------------------------------------------------------------
  Copyright (C) - Antonio De Vincentiis anno 2020
  tel.3383121161
  a.devincentiis@tiscali.it
  Montesilvano (PE)
  --------------------------------------------------------------------------

*/

class opencarttreForm extends GAzieForm {
   function toast($message, $id = 'alert-discount', $class = 'alert-warning') {
        /*
          echo "<script type='text/javascript'>toast('$message');</script>"; */
        if (!empty($message)) {
            echo '<div class="container">
					<div id="' . $id . '" class="row alert ' . $class . ' fade in" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
							<span aria-hidden="true">&times;</span>
						</button>
						<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>&nbsp;' . $message . '
					</div>
				  </div>';
        }
        return '';
    }
}

?>
