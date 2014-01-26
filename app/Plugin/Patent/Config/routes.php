<?php
Router::connect('/patent/:action/*', array(
	'controller' => 'patent',
	'plugin' => 'Patent'
));