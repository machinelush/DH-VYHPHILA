<blockquote class="cfct-mod-content"<?php if (!empty($attribution_url)) { echo 'cite="'.$attribution_url.'"'; } ?>>
	<?php 
		if (!empty($data[$this->get_field_id('content')])) {
			echo $this->wp_formatting($data[$this->get_field_id('content')]);
		}
		if (!empty($attribution)) {
			if (!empty($attribution_url)) {
				$attribution = '<a href="'.$attribution_url.'">'.$attribution.'</a>';
			} 
			echo '<cite>'.$attribution.'</cite>';
		} 
	?>
</blockquote>