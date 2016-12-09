<?php
if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}
/**
 * Find unused plugins on a multisite network.
 *
 * Iterates through all sites on a network to find plugins which aren't enabled
 * on any site.
 */
$find_unused_plugins_command = function() {
    $response = WP_CLI::launch_self( 'site list', array(), array( 'format' => 'json' ), false, true );
    $sites = json_decode( $response->stdout );
    $unused = array();
    $used = array();
    foreach( $sites as $site ) {
        WP_CLI::log( "Checking {$site->url} for unused plugins..." );
        $response = WP_CLI::launch_self( 'plugin list', array(), array( 'url' => $site->url, 'format' => 'json' ), false, true );
        $plugins = json_decode( $response->stdout );
        foreach( $plugins as $plugin ) {
            if ( 'inactive' == $plugin->status && ! in_array( $plugin->name, $used ) ) {
                $unused[ $plugin->name ] = $plugin;
            } else {
                if ( isset( $unused[ $plugin->name ] ) ) {
                    unset( $unused[ $plugin->name ] );
                }
                $used[] = $plugin->name;
            }
        }
    }
    WP_CLI\Utils\format_items( 'table', $unused, array( 'name', 'version' ) );
};
WP_CLI::add_command( 'find-unused-plugins', $find_unused_plugins_command, array(
    'before_invoke' => function(){
        if ( ! is_multisite() ) {
            WP_CLI::error( 'This is not a multisite install.' );
        }
    },
) );