<?php 
/*  Copyright 2012  ROMAN MANUKYAN  (email : romanwebdev@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die("Cannot access pages directly.");
defined("DS") or define("DS", DIRECTORY_SEPARATOR);

class Gzalien_APO_Widget extends WP_Widget
{
    
    protected $pluginUrl;
    protected $pluginEditPageUrl;
    
	/**
	 * Constructor
	 * 
	 * Registers the widget details with the parent class
	 */
	function Gzalien_APO_Widget()
	{
        
        $this->pluginEditPageUrl =  '/wp-admin/plugins.php?page='. Gzalien_APO::PLUGIN_DIR_NAME.'/index.php';
       
        $this->pluginUrl = WP_PLUGIN_URL . '/' . Gzalien_APO::PLUGIN_DIR_NAME;
        
        
        $plugName = Gzalien_APO::PLUGIN_NAME;
        
        $widget_options = array(
            'description' => __( "You can customize the widget from \"Plugins > {$plugName}\"" )
        );
        
        
		parent::WP_Widget( 
                    'gzalien_apo_widget', 
                    __('Gzalien Available Payment Options', get_class($this)),
                    $widget_options 
                );
        
	}

	function form($instance)
	{     
        $panelTxt = "You can customize the widget <a href=\"{$this->pluginEditPageUrl}\">here</a>";
        
		?>
        <div id="poa-admin-panel" >
            <h3><?=$panelTxt?></h3>
        </div>
		
		<?php 
	}
    
    protected function hasOption( $key ){
        $val = get_option( Gzalien_APO::PLUGIN_PREFIX . $key );
        return $val === false ? false : true;
    }

    protected function setOption( $key, $value ){
        update_option( Gzalien_APO::PLUGIN_PREFIX . $key, $value );
    }
    
    protected function getOption( $key, $default = null ){
        
        $val = get_option( Gzalien_APO::PLUGIN_PREFIX . $key );
        
        if( $val === false ){
            $val = $default;
        }
        
        return $val;        
    }

	function update($new_instance, $old_instance)
	{
        $instance = $old_instance;
		return $instance;
	}

	function widget($args, $instance)
	{           
        if( !$this->getOption( 'initialized')  ){
            echo 'Please initialize the widget';
            return;
        }else{
            $data = unserialize($this->getOption('data'));
        }      
		?>
        <!-- Available Payment Options Widget by Roman Manukyan (gzalien.com), email: romanwebdev@gmail.com -->
        <style type="text/css" >
            #gzalien-apo-panel-wrapper{ width:100%; text-align:center;}
            #gzalien-apo-panel{  display: inline-block; }
            #gzalien-apo-panel ul{ 
                background-color: <?= $data['panelBgColor'] ?>;
                padding: 5px;
                list-style: none;
                border-radius:4px; 
                <?php if( strlen($data['panelBorder']) > 0 ): ?>
                border: <?=$data['panelBorder']?>;
                <?php endif; ?>
            } 
            #gzalien-apo-panel ul li img{ vertical-align: top; }
            #gzalien-apo-panel ul li{ float: left; margin-right: 10px; margin-bottom: 0px;}   
            #gzalien-apo-panel ul li.last{ margin-right: 0;}
            #gzalien-apo-panel h3{ margin-bottom: 5px; color:<?= $data['headerTextColor'] ?>; font-weight: bold;}
            
            #gzalien-apo-panel .apo-clearfix:after { content: "."; display: block; clear: both; visibility: hidden; line-height: 0; height: 0;}
            #gzalien-apo-panel .apo-clearfix { display: inline-block; }
            html[xmlns] #gzalien-apo-panel .apo-clearfix { display: block; } 
            * html #gzalien-apo-panel .apo-clearfix { height: 1%; }            
            
        </style>
        <div id="gzalien-apo-panel-wrapper">
            <div id="gzalien-apo-panel" class="<?=$data['panelCssClass']?>">
                <?php if( strlen($data['headerText']) > 0 ):?>
                <h3><?= $data['headerText'] ?></h3>
                <?php endif; ?>

                <ul class="apo-clearfix">
                    <?php $i = 0; $len = count($data['methods']); ?>
                    <?php foreach ($data['methods'] as $method): ?>
                    
                    <?php $thumb = "{$this->pluginUrl}/images/icons/{$data['size']}/{$method}-{$data['style']}-{$data['size']}px.png"; ?>
                    
                    <li class="<?= $i == $len-1 ? 'last' : ''?>" >
                        <img src="<?= $thumb ?>" alt="<?= $data['methodCaptions'][$method]?>"  title="<?= $data['methodCaptions'][$method]?>"/>
                    </li>         
                    <?php $i++; ?>
                    <?php endforeach; ?>
                    <div style="clear:both;"></div>
                </ul>
            </div>
		</div>
        <!-- end of Available Payment Options Widget -->
		<?php 
	}

}