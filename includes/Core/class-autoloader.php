<?php
namespace HAO\Core;

defined( 'ABSPATH' ) || exit;

/**
 * PSR-4 Uyumlu Sınıf Yükleyici
 */
class Autoloader {

    public static function register() {
        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }

    public static function autoload( $class ) {
        $prefix = 'HAO\\';
        $base_dir = HAO_DIR . 'includes/';

        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, $len );
        $parts = explode( '\\', $relative_class );
        
        $class_name = array_pop( $parts );
        
        // Sınıf dosya adlandırma standartı: class-kebab-case.php
        $file_name = 'class-' . strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_name ) ) . '.php';
        
        $sub_path = empty( $parts ) ? '' : implode( '/', $parts ) . '/';
        $file = $base_dir . $sub_path . $file_name;

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
