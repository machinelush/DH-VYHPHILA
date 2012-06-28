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
    @$mainurl = get_option('siteurl')."/wp-admin/options-general.php?page=wp-anything-slider/wp-anything-slider.php";
    @$DID=@$_GET["DID"];
    @$AC=@$_GET["AC"];
    @$submittext = "Insert Message";
	if($AC <> "DEL" and trim(@$_POST['wpanything_csetting']) <>"")
    {
		if($_POST['wpanything_cid'] == "" )
		{
			$sql = "insert into ".WP_ANYTHING_CONTENT.""
			. " set `wpanything_ctitle` = '" . trim($_POST['content'])
			. "', `wpanything_csetting` = '" . $_POST['wpanything_csetting']
			. "'";	
		}
		else
		{
			$sql = "update ".WP_ANYTHING_CONTENT.""
			. " set `wpanything_ctitle` = '" . trim($_POST['content'])
			. "', `wpanything_csetting` = '" . $_POST['wpanything_csetting']
			. "' where `wpanything_cid` = '" . $_POST['wpanything_cid'] 
			. "'";	
		}
		$wpdb->get_results($sql);
    }
    
    if($AC=="DEL" && $DID > 0)
    {
        $wpdb->get_results("delete from ".WP_ANYTHING_CONTENT." where wpanything_cid=".$DID);
    }
    
    if($DID<>"" and $AC <> "DEL")
    {
        $data = $wpdb->get_results("select * from ".WP_ANYTHING_CONTENT." where wpanything_cid=$DID limit 1");
        if ( empty($data) ) 
        {
           echo "<div id='message' class='error'><p>No data available! use below form to create!</p></div>";
           return;
        }
        $data = $data[0];
        if ( !empty($data) ) $wpanything_cid_x = $data->wpanything_cid; 
        if ( !empty($data) ) $wpanything_ctitle_x = $data->wpanything_ctitle;
		if ( !empty($data) ) $wpanything_csetting_x = $data->wpanything_csetting;
        $submittext = "Update Message";
    }
	add_filter('admin_head','ShowTinyMCE');
    function ShowTinyMCE() 
	{
        // conditions here
        wp_enqueue_script( 'common' );
        wp_enqueue_script( 'jquery-color' );
        wp_print_scripts('editor');
        if (function_exists('add_thickbox')) add_thickbox();
        wp_print_scripts('media-upload');
        if (function_exists('wp_tiny_mce')) wp_tiny_mce();
        wp_admin_css();
        wp_enqueue_script('utils');
        do_action("admin_print_styles-post-php");
        do_action('admin_print_styles');
    }

    ?>
  <h2>Wp anything slider</h2>
  <script language="JavaScript" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/wp-anything-slider/setting.js"></script>
  <form name="wpanything_content_form" method="post" action="<?php echo $mainurl; ?>" onsubmit="return wpanything_content_submit()"  >
    <table width="100%">
      <tr>
        <td align="left" valign="middle"><?php wp_editor(@$wpanything_ctitle_x, "content");?>
        </td>
      </tr>
      <tr>
        <td align="left" valign="middle">Setting name:</td>
      </tr>
      <tr>
        <td width="11%" align="left" valign="middle"><select name="wpanything_csetting" id="wpanything_csetting">
            <option value="">Select</option>
            <?php
            for($i=1; $i<=10; $i++)
			{
				if(@$wpanything_csetting_x == 'SETTING'.$i) 
				{ 
					$selected = 'selected' ; 
				}
				else
				{
					$selected = '' ; 
				}
				echo "<option value='SETTING".$i."' $selected>SETTING".$i."</option>";
			}
			?>
          </select>
        </td>
      </tr>
      <tr>
        <td height="35" align="left" valign="bottom"><table width="100%">
            <tr>
              <td width="50%" align="left"><input name="publish" lang="publish" class="button-primary" value="<?php echo @$submittext?>" type="submit" />
                <input name="publish" lang="publish" class="button-primary" onclick="wpanything_content_redirect()" value="Cancel" type="button" />
              </td>
              <td width="50%" align="right"><input name="text_management1" lang="text_management" class="button-primary" onClick="location.href='options-general.php?page=wp-anything-slider/wp-anything-slider.php'" value="Go to - Text Management" type="button" />
                <input name="setting_management1" lang="setting_management" class="button-primary" onClick="location.href='options-general.php?page=wp-anything-slider/cycle-setting.php'" value="Go to - Setting Management" type="button" />
                <input name="Help1" lang="publish" class="button-primary" onclick="wpanything_help()" value="Help" type="button" />
              </td>
            </tr>
          </table></td>
      </tr>
      <input name="wpanything_cid" id="wpanything_cid" type="hidden" value="<?php echo @$wpanything_cid_x; ?>">
    </table>
  </form>
  <div class="tool-box">
    <?php
	$data = $wpdb->get_results("select * from ".WP_ANYTHING_CONTENT." order by wpanything_cstartdate");
	if ( empty($data) ) 
	{ 
		echo "<div id='message' class='error'>No data available! use below form to create!</div>";
		return;
	}
	?>
    <form name="wpanything_content_display" method="post">
      <table width="100%" class="widefat" id="straymanage">
        <thead>
          <tr>
            <th align="left" scope="col">No</th>
            <th align="left" scope="col">Announcement</th>
            <th align="left" scope="col">Setting</th>
            <th align="left" scope="col">Action</th>
          </tr>
        </thead>
        <?php 
        $i = 0;
        foreach ( $data as $data ) { 
		//echo date("Y-m-d")."<br>";
        ?>
        <tbody>
          <tr class="<?php if ($i&1) { echo'alternate'; } else { echo ''; }?>">
            <td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_cid)); ?></td>
            <td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_ctitle)); ?></td>
            <td align="left" valign="middle"><?php echo(stripslashes($data->wpanything_csetting)); ?></td>
            <td align="left" valign="middle"><a href="options-general.php?page=wp-anything-slider/wp-anything-slider.php&DID=<?php echo($data->wpanything_cid); ?>">Edit</a> &nbsp; <a onClick="javascript:wpanything_content_delete('<?php echo($data->wpanything_cid); ?>')" href="javascript:void(0);">Delete</a> </td>
          </tr>
        </tbody>
        <?php $i = $i+1; } ?>
      </table>
    </form>
  </div>
  <table width="100%">
    <tr>
      <td align="right"><span style="float:left;">
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
