<?php

if (!class_exists('cfct_module_pullquote')) {
	class cfct_module_pullquote extends cfct_build_module {
		protected $_deprecated_id = 'cfct-pullquote'; // deprecated property, not needed for new module development

		public function __construct() {
			$opts = array(
				'description' => __('Insert a stylized pull-quote into the layout.', 'carrington-build'),
				'icon' => 'pullquote/icon.png' // relative to /path/to/carrington-build/modules
			);
			parent::__construct('cfct-pullquote', __('Pull Quote', 'carrington-build'), $opts);
		}

		public function display($data) {
			$attribution = '';
			$attribution_url = '';
			if (!empty($data[$this->get_field_name('attribution')])) {
				$attribution = esc_html($data[$this->get_field_name('attribution')]);
			}
			if (!empty($data[$this->get_field_name('attribution_url')])) {
				$attribution_url = esc_attr($data[$this->get_field_name('attribution_url')]);
			}
			
			return $this->load_view($data, compact('attribution', 'attribution_url'));
		}

		public function admin_form($data) {
			$ret = '
				<div>
					<label for="'.$this->get_field_id('content').'">Quote Text</label>
					<textarea class="cfct-textarea-full" name="'.$this->get_field_name('content').'" id="'.$this->get_field_id('content').'">'.
						(isset($data[$this->get_field_name('content')]) ? htmlspecialchars($data[$this->get_field_name('content')]) : null).
					'</textarea>
				</div>
				<div class="cfct-inline-els">
					<label for="'.$this->get_field_id('attribution').'">Attribution</label>
					<input type="text" name="'.$this->get_field_name('attribution').'" id="'.$this->get_field_id('attribution').'" value="'.(isset($data[$this->get_field_name('attribution')]) ? esc_attr($data[$this->get_field_name('attribution')]) : null).'" />
				</div>
				<div class="cfct-inline-els">
					<label for="'.$this->get_field_id('attribution_url').'">Attribution URL</label>
					<input type="text" name="'.$this->get_field_name('attribution_url').'" id="'.$this->get_field_id('attribution_url').'" value="'.(isset($data[$this->get_field_name('attribution_url')]) ? esc_attr($data[$this->get_field_name('attribution_url')]) : null).'" />
				</div>
				';
			return $ret;
		}

		/**
		 * Return a textual representation of this module.
		 *
		 * @param array $data 
		 * @return string
		 */
		public function text($data) {
			return $data[$this->get_field_name('content')];
		}


		/**
		 * Modify the data before it is saved, or not
		 *
		 * @param array $new_data 
		 * @param array $old_data 
		 * @return array
		 */
		public function update($new_data, $old_data) {
			return $new_data;
		}
	
		/**
		 * Add some admin CSS for formatting
		 *
		 * @return void
		 */
		public function admin_css() {
			return '
				#'.$this->get_field_id('content').' {
					height: 300px;
				}
			';
		}
	
		/**
		 * Add some client side CSS
		 *
		 * @return void
		 */
		public function css() {
			return '
				.'.$this->id_base.' {
					display: block;
					position: relative;
					padding: 0;
					padding: 10px 0 10px 10px;
				}
				.'.$this->id_base.' .'.$this->id_base.'-inner {
					display: block;
					margin: 0;
					padding: 0;
					border-left: 0;
					line-height: 1.5em;
				}
				.'.$this->id_base.' .'.$this->id_base.'-open-quote, 
				.'.$this->id_base.' .'.$this->id_base.'-close-quote {
					font-size: 80px;
					color: gray;
					height: 18px;
				}
				.'.$this->id_base.' .'.$this->id_base.'-open-quote {
					float: left;
					margin-top: -10px;
					line-height: .8em;
				}
				.'.$this->id_base.' .'.$this->id_base.'-close-quote {
					float: right;
					margin-top: 10px;
					line-height: 0em;
				}
				';
		}
	}
	cfct_build_register_module('cfct_module_pullquote');
}

?>