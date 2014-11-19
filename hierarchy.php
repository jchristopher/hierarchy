<?php
/*
 Plugin Name: Hierarchy
 Plugin URI: http://mondaybynoon.com/wordpress-hierarchy/
 Description: Properly structure your Pages, Posts, and Custom Post Types
 Version: 0.6
 Author: Jonathan Christopher
 Author URI: http://mondaybynoon.com/
*/

/*  Copyright 2012-2014 Jonathan Christopher (email : jonathan@irontoiron.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( is_admin() ) {
	require plugin_dir_path( __FILE__ ) . 'includes/class-hierarchy.php';
	
	$hierarchy = new Hierarchy();
	$hierarchy->init();
}