<?php
/*
  Plugin Name: Available Payment Options 
  Plugin URI: http://gzalien.com/available-payment-options-wordpress-plugin
  Description: This widget solves one common task for web-applications, it shows payment options available at your website.
  Author: Roman Manukyan
  Version: 1.0.9
  Author URI: http://gzalien.com
 */

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

include_once( WP_PLUGIN_DIR .'/available-payment-options-plugin/widget.php' );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


add_action('widgets_init', create_function( '', 'return register_widget("Gzalien_APO_Widget");' ) );

new Gzalien_APO();

class Gzalien_APO {

    const PLUGIN_PREFIX = 'Gzalien_AvailablePaymentOptions_';
    const PLUGIN_NAME   = 'Gzalien Available Payment Options';
    const PLUGIN_DIR_NAME = 'available-payment-options-plugin';
    
    protected $pluginPath;
    protected $pluginUrl;
    protected $thisFile;
    protected $states;
    
    protected $id    = null;
    
    public function Gzalien_APO() {
        $this->__construct();        
    }
    
    public function __construct() {
        
        if( ! is_admin() ) {
            return;
        }
               
        $this->pluginPath = dirname(__FILE__);
        $this->thisFile   = __FILE__;
        $this->pluginUrl  = WP_PLUGIN_URL . '/' . self::PLUGIN_DIR_NAME;
        
        $this->iconSizes  = array(32,64,128);
        $this->iconStyles = array( 'curved', 'straight' );
        
        $this->methods = array(
            
           'American Express'=> 'american-express',
           'Cirrus'=>  'cirrus',
           'Delta'=>  'delta',
           'Direct Debit'=>  'direct-debit',
           'Discover'=>  'discover',
           'Maestro'=>  'maestro',
           'Master Card'=>  'mastercard',
           'Pay Pal'=>  'paypal',
           'Solo'=>  'solo',
           'Switch'=>  'switch',
           'Visa Electron'=>  'visa-electron',
           'Visa'=>  'visa',
           'Western Union'=>  'western-union'
        );
        
        $this->initOptions();
                
        add_action( 'admin_menu', array( &$this, 'configPage' ));        
        add_filter( 'the_content', array(&$this, 'fContent') ); 
        add_action( 'wp_ajax_gzalien_apo_save', array(&$this, 'saveInfo') );
       
        add_action('init', create_function( '', 'return register_widget("Gzalien_APO_Widget");' ) );
    }
   
    
  /** Adding category via AJAX */
  public function saveInfo(){
        global $wpdb; 
                
        $res = array(
            'success' => true
        );
        
        if( !isset( $_POST['saveData'] ) ){
            $res['error'] = 'Parameter "data" was not passed';
            $res['success'] = false;
        } else {
            
            $data = $_POST['saveData'];            
            $this->setOption('data', serialize( $data ));                    
        }        
        
        echo json_encode( $res );
        die();
    }
    
       
    protected function getOption( $key, $default = null ){
        
        $val = get_option( self::PLUGIN_PREFIX . $key );
        
        if( $val === false ){
            $val = $default;
        }
        
        return $val;        
    }
    
    protected function hasOption( $key ){
        $val = get_option( self::PLUGIN_PREFIX . $key );
        return $val === false ? false : true;
    }

    protected function setOption( $key, $value ){
        update_option( self::PLUGIN_PREFIX . $key, $value );
    }
    
    
    public function initOptions() {
        
        if ( !get_option( self::PLUGIN_PREFIX . 'initialized') ) {
            
            $data = array(
                'methods'         => array(),
                'methodCaptions'  => (object)array(),
                'size'            => 64,
                'style'           => 'curved', 
                'headerText'      => 'We Accept These Payment Methods',
                'headerTextColor' => '#000000',
                'panelBgColor'    => '#efefef',
                'panelBorder'     => '1px solid #333',
                'panelCssClass'   => 'my-po-panel'
            );
            
            $this->setOption( 'data', serialize($data));
            $this->setOption( 'initialized',     '1');  
        }
    }

    public function configPage() {
        
        if (function_exists('add_submenu_page')) {
            add_submenu_page(
                    'plugins.php', 
                    self::PLUGIN_NAME . ' Config',
                    self::PLUGIN_NAME,
                    'manage_options', 
                    __FILE__,
                    array( &$this, 'conf') 
                    );
        }
    }

    /** Plugin configuration page in wp-admin **/
    public function conf() {
        
        
        wp_enqueue_style( false, $this->pluginUrl . '/bootstrap/css/bootstrap.css' );
        wp_enqueue_script( 'bootstrap', $this->pluginUrl . '/bootstrap/js/bootstrap.js', array('jquery' ) );
                              
        wp_enqueue_style( 'Gzalien_AvailablePaymentOptions_style', $this->pluginUrl . '/css/style.css' );
        
        wp_enqueue_script( 'Gzalien_AvailablePaymentOptions_blockui_js', $this->pluginUrl . '/js/blockui.js', array('jquery' ));
        wp_enqueue_script( 'Gzalien_AvailablePaymentOptions_underscore_js', $this->pluginUrl . '/js/underscore.js', array('jquery' ));
        wp_enqueue_script( 'Gzalien_AvailablePaymentOptions_backbone_js', $this->pluginUrl . '/js/backbone.js', array('jquery', 'Gzalien_AvailablePaymentOptions_underscore_js' ));
        wp_enqueue_script( 'Gzalien_AvailablePaymentOptions_textchange_js', $this->pluginUrl . '/js/textchange.js', array('jquery' ));
        wp_enqueue_script( 'Gzalien_AvailablePaymentOptions_script_js', $this->pluginUrl . '/js/script.js', array('jquery', 'Gzalien_AvailablePaymentOptions_textchange_js', 'Gzalien_AvailablePaymentOptions_backbone_js' ));    
        
        $data = unserialize( $this->getOption('data') );
        $activeSize = $data['size'];
                ?>
                
        <script type="text/javascript" >

            jQuery('document').ready( function(){  
                    __gzalienAPOModel = new GzalienAPO({
                        
                        'methods'         : <?=json_encode( $data['methods'] )?>,
                        'methodCaptions'  : <?=json_encode( $data['methodCaptions'] )?>,
                        'size'            : <?=$data['size']?>,
                        'style'           : '<?=$data['style']?>', 
                        'headerText'      : '<?=$data['headerText']?>',
                        'headerTextColor' : '<?=$data['headerTextColor']?>',
                        'panelBgColor'    : '<?=$data['panelBgColor']?>',
                        'panelBorder'     : '<?=$data['panelBorder']?>',
                        'panelCssClass'   : '<?=$data['panelCssClass']?>'
                    });

                __gzalienAMOView = new GzalienAPOView({
                    imagesBase : '<?=$this->pluginUrl?>/images/icons/',
                    el: jQuery("#gridSystem"),
                    model : __gzalienAPOModel
                });                  

            });
        </script>     
        
        
        <div class="amazon-ads" style="position: absolute;top: 105px;right: 125px;width: 100px;">
            <div style="color:red;font-weight: bold;text-align: center;" >Amazing Books</div>
            <iframe src="http://rcm.amazon.com/e/cm?t=nycboroscom-20&o=1&p=8&l=as1&asins=1449304214&ref=tf_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0087E1&bc1=FFFFFF&bg1=FFFFFF&npa=1&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
            <iframe src="http://rcm.amazon.com/e/cm?t=nycboroscom-20&o=1&p=8&l=as1&asins=0470935030&nou=1&ref=tf_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0087E1&bc1=FFFFFF&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
            <iframe src="http://rcm.amazon.com/e/cm?t=nycboroscom-20&o=1&p=8&l=as1&asins=1118066901&ref=tf_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0087E1&bc1=FFFFFF&bg1=FFFFFF&npa=1&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
            <iframe src="http://rcm.amazon.com/e/cm?t=nycboroscom-20&o=1&p=8&l=as1&asins=0963330268&ref=tf_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0087E1&bc1=FFFFFF&bg1=FFFFFF&npa=1&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
            <iframe src="http://rcm.amazon.com/e/cm?t=nycboroscom-20&o=1&p=8&l=as1&asins=1419690000&ref=tf_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0087E1&bc1=FFFFFF&bg1=FFFFFF&npa=1&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
            <iframe src="http://rcm.amazon.com/e/cm?t=nycboroscom-20&o=1&p=8&l=as1&asins=1118123190&ref=tf_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0087E1&bc1=FFFFFF&bg1=FFFFFF&npa=1&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
        </div>

        <div class="page-header">
            <h1>Available Payment Options by <a href="http://gzalien.com?ref=apo-wp-plugin">Gzalien.com</a></h1>
        </div>
        <div id="gridSystem" >
            <div class="row preview-row">
                <div class="span12">
                    <h3>Preview of the widget<span class="click-to-rem-label">Click on icons to remove</span></h3>
                    <div class="preview-wrap">
                        
                        <div id="preview" >
                            Preview will be here
                        </div>
                    </div>    
                </div>
            </div>
            <div class="row">
                
                 <div class="span12">
                    <form class="form-horizontal">
                    <fieldset>
                        <legend>Customize the panel</legend>
                        
                        <div class="row" >
                            <div class="span5">
                                <div class="control-group">
                                    <label class="control-label" for="apo-header-text">Header</label>
                                    <div class="controls">
                                        <input type="text" class="input-large" id="apo-header-text" value="<?= $data['headerText'] ?>" />                            
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label class="control-label" for="input01">Header Text Color</label>
                                    <div class="controls">
                                        <input type="text" class="input-small" id="apo-header-text-color" value="<?= $data['headerTextColor'] ?>" />
                                        <p class="help-block">e.g. #000000</p>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="apo-panel-bg-color">Background Color</label>
                                    <div class="controls">
                                        <input type="text" class="input-large apo_panel_bg_color" id="apo-panel-bg-color" value="<?= $data['panelBgColor'] ?>" />
                                        <p class="help-block">e.g. #efefef</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="span6">
                                <div class="control-group">
                                    <label class="control-label" for="input01">Border</label>
                                    <div class="controls">
                                        <input type="text" class="input-medium" id="apo-panel-border" value="<?= $data['panelBorder'] ?>" />
                                        <p class="help-block">e.g. #efefef</p>
                                    </div>
                                </div>
                        
                                <div class="control-group">
                                    <label class="control-label" for="input01">CSS Class</label>
                                    <div class="controls">
                                        <input type="text" class="input-medium" id="apo-panel-css-class" value="<?= $data['panelCssClass'] ?>" />
                                        <p class="help-block">e.g. my-pretty-panel</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </fieldset>
                    </form>
                 </div>                
            </div>
            
            <div class="row">
                <div class="span9">
                    <form class="form-horizontal">
                        <fieldset>
                            <legend>Choose Methods</legend>
                            
                            <?php 
                            $images = array();
                            ?>
                            
                            <div class="tabbable" id="images-tab"> <!-- Only required for left/right tabs -->
                               
                                <ul class="nav nav-tabs">
                                    <?php $i=0; ?>
                                    <?php foreach ($this->iconSizes as $size): ?>
                                         <li class="<?= $size == $activeSize ? 'active' : '' ?>"><a href="#tab-size-<?=$size?>" data-toggle="tab"><?=$size?> px</a></li>  
                                         <?php $i++;?>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="tab-content">
                                    <?php $i=0; ?>
                                    <?php foreach( $this->iconSizes as $size ): ?>
                                    
                                    <div class="tab-pane <?= $size == $activeSize ? 'active' : ''?>" id="tab-size-<?=$size?>">

                                            <?php $iconStyle = $this->iconStyles[0]; ?>
                                            <?php foreach( $this->methods as $methodName => $methodSlug ): ?>
                                            <?php
                                            
                                            $thumb = "{$this->pluginUrl}/images/icons/{$size}/{$methodSlug}-{$iconStyle}-{$size}px.png";
                                            
                                            ?>

                                            <div class="thumbnail" 
                                                 apo-method="<?=$methodSlug?>" 
                                                 apo-size="<?=$size?>" 
                                                 apo-style="<?=$iconStyle?>" 
                                                 apo-caption="<?= htmlentities( $methodName ) ?>">
                                            <img src="<?=$thumb?>" alt=""/>
                                            <h5><?=$methodName?></h5>
                                            </div>
                                            
                                            <?php endforeach; ?>    
                                    </div>
                                  
                                     <?php $i++;?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            
                            
                            
                        </fieldset>
                    </form>
                </div>
            </div>
            
           <div class="row" >
                <div class="span10">
                    <div class="well save-panel">
                        <button id="apo-save" class="btn btn-primary" >Save</button>                                
                    </div>
                    
                    <div class="donate">
                        <h3>Found this plugin valuable ? Support development of new versions and other cool plugins !</h3>
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="">
                            <input type="hidden" name="cmd" value="_donations" />
                            <input type="hidden" name="business" value="UW9MCB7U2EB7N" />
                            <input type="hidden" name="lc" value="US" />
                            <input type="hidden" name="item_name" value="Gzalien.com Availabe Payment Options Plugin" />
                            <input type="hidden" name="currency_code" value="USD" />
                            <input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" />
                            <input type="image" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="PayPal - The safer, easier way to pay online!" />
                            <img src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" alt="" width="1" height="1" border="0" />
                        </form>
                    </div>
                    <img src="http://gzalien.com?track=available-payment-options" style="display: none;"/>                    
                </div>
            </div>
            
        </div>    

        <?
    } // end conf
  
}
?>
