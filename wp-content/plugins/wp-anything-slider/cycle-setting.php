<!--
 *     Wp anything slider
 *     Copyright (C) 2012  www.gopiplus.com
 * 
 *     This program is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 * 
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 * 
 *     You should have received a copy of the GNU General Public License
 *     along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<div class="wrap">
  <?php
  	global $wpdb;
    @$mainurl = get_option('siteurl')."/wp-admin/options-general.php?page=wp-anything-slider/cycle-setting.php";
    @$DID=@$_GET["DID"];
    @$AC=@$_GET["AC"];
	if(trim(@$_POST['wpanything_sdirection']) <> "")
    {
		if(!$_POST['wpanything_sid'] == "" )
		{
			$sql = "update ".WP_ANYTHING_SETTINGS.""
			. " set `wpanything_sdirection` = '" . trim($_POST['wpanything_sdirection'])
			. "', `wpanything_sspeed` = '" . trim($_POST['wpanything_sspeed'])
			. "', `wpanything_stimeout` = '" . trim($_POST['wpanything_stimeout'])
			. "' where `wpanything_sid` = '" . trim($_POST['wpanything_sid'] )
			. "'";	
			$wpdb->get_results($sql);
		}
    }
    
    if($DID <> "")
    {
        $data = $wpdb->get_results("select * from ".WP_ANYTHING_SETTINGS." where wpanything_sid=$DID limit 1");
        if ( empty($data) ) 
        {
           echo "<div id='message' class='error'><p>No data available!</p></div>";
           return;
        }
        $data = $data[0];
        if ( !empty($data) ) $wpanything_sid_x = htmlspecialchars(stripslashes($data->wpanything_sid)); 
        if ( !empty($data) ) $wpanything_sname_x = htmlspecialchars(stripslashes($data->wpanything_sname));
        if ( !empty($data) ) $wpanything_sdirection_x = htmlspecialchars(stripslashes($data->wpanything_sdirection));
		if ( !empty($data) ) $wpanything_sspeed_x = htmlspecialchars(stripslashes($data->wpanything_sspeed));
		if ( !empty($data) ) $wpanything_stimeout_x = htmlspecialchars(stripslashes($data->wpanything_stimeout));
    }
    ?>
  <h2>Wp anything slider setting</h2>
  <script language="JavaScript" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/wp-anything-slider/setting.js"></script>
  <form name="wpanything_setting_form" method="post" action="<?php echo $mainurl; ?>" onsubmit="return wpanything_setting_submit()"  >
    <table width="100%" border="0" cellspacing="0" cellpadding="5">
      <tr>
        <td align="left">Speed</td>
      </tr>
      <tr>
        <td align="left"><input name="wpanything_sspeed" type="text" id="wpanything_sspeed" value="<?php echo @$wpanything_sspeed_x; ?>" maxlength="5" /> (Ex: 700)</td>
      </tr>
      <tr>
        <td align="left">Timeout</td>
      </tr>
      <tr>
        <td align="left"><input name="wpanything_stimeout" type="text" id="wpanything_stimeout" value="<?php echo @$wpanything_stimeout_x; ?>" maxlength="5" /> (Ex: 5000)</td>
      </tr>
      <tr>
        <td align="left">Direction</td>
      </tr>
      <tr>
        <td align="left"><select name="wpanything_sdirection" id="wpanything_sdirection">
            <option value=""></option>
            <option value='scrollLeft' <?php if(@$wpanything_sdirection_x=='scrollLeft') { echo 'selected' ; } ?>>scrollLeft</option>
            <option value='scrollRight' <?php if(@$wpanything_sdirection_x=='scrollRight') { echo 'selected' ; } ?>>scrollRight</option>
            <option value='scrollUp' <?php if(@$wpanything_sdirection_x=='scrollUp') { echo 'selected' ; } ?>>scrollUp</option>
            <option value='scrollDown' <?php if(@$wpanything_sdirection_x=='scrollDown') { echo 'selected' ; } ?>>scrollDown</option>
          </select>
        </td>
      </tr>
      <tr>
        <td align="left">
			<?php  if($DID <> "") { ?>
			<input name="publish" lang="publish" class="button-primary" value="Update Setting" type="submit" />
			<input name="publish" lang="publish" class="button-primary" onclick="wpanything_setting_redirect()" value="Cancel" type="button" />
			<?php } ?>
			<span style="float:right;">
			<input name="text_management" lang="text_management" class="button-primary" onClick="location.href='options-general.php?page=wp-anything-slider/wp-anything-slider.php'" value="Go to - Text Management" type="button" />
			<input name="setting_management" lang="setting_management" class="button-primary" onClick="location.href='options-general.php?page=wp-anything-slider/cycle-setting.php'" value="Go to - Setting Management" type="button" />
			<input name="Help1" lang="publish" class="button-primary" onclick="wpanything_help()" value="Help" type="button" />
			</span>
		</td>
      </tr>  
    </table>
    <input name="wpanything_sid" id="wpanything_sid" type="hidden" value="<?php echo @$wpanything_sid_x; ?>">
  </form>
  <div class="tool-box">
    <?php
	$data = $wpdb->get_results("select * from ".WP_ANYTHING_SETTINGS." order by wpanything_sid");
	if ( empty($data) ) 
	{ 
		echo "<div id='message' class='error'>No data available</div>";
		return;
	}
	?>
    <form name="wpanything_Display" method="post">
      <table width="100%" class="widefat" id="straymanage">
        <thead>
          <tr>
            <th align="left" scope="col">No</th>
            <th align="left" scope="col">Setting name</th>
			<th align="left" scope="col">Short code</th>
			<th align="left" scope="col">Speed</th>
			<th align="left" scope="col">Timeout</th>
            <th align="left" scope="col">Direction</th>
			<th align="left" scope="col">Action</th>
          </tr>
        </thead>
        <?php 
        $i = 0;
        foreach ( $data as $data ) { 
        ?>
        <tbody>
          <tr class="<?php if ($i&1) { echo'alternate'; } else { echo ''; }?>">
            <td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_sid)); ?></td>
			<td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_sname)); ?></td>
            <td align="left" valign="middle">[wp-anything-slider setting="<?php echo(stripslashes($data->wpanything_sname)); ?>"]</td>
			<td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_sspeed)); ?></td>
            <td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_stimeout)); ?></td>
            <td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_sdirection)); ?></td>
            <td align="left" valign="middle"><a href="options-general.php?page=wp-anything-slider/cycle-setting.php&DID=<?php echo($data->wpanything_sid); ?>">Click to edit</a></td>
          </tr>
        </tbody>
        <?php $i = $i+1; } ?>
      </table>
    </form>
  </div>
  <table width="100%">
    <tr>
      <td align="right">
	  <span style="float:left;vertical-align:top;">
	  <ul>
	  <li>Check official website for live demo and more help <a href="http://www.gopiplus.com/work/2012/04/20/wordpress-plugin-wp-anything-slider/" target="_blank">click here</a></li>
	  </ul>
	  </span>
	  <input name="text_management" lang="text_management" class="button-primary" onClick="location.href='options-general.php?page=wp-anything-slider/wp-anything-slider.php'" value="Go to - Text Management" type="button" />
      <input name="setting_management" lang="setting_management" class="button-primary" onClick="location.href='options-general.php?page=wp-anything-slider/cycle-setting.php'" value="Go to - Setting Management" type="button" />
	  <input name="Help" lang="publish" class="button-primary" onclick="wpanything_help()" value="Help" type="button" />
      </td>
    </tr>
  </table>
</div>