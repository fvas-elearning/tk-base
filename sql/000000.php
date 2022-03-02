<?php
/**
 * @version 3.0
 *
 * @author: Michael Mifsud <info@tropotek.com>
 */

// This is executed here to let the \Tk\Db\Data object control the creation of the Data table.
$config = \Bs\Config::getInstance();
try {
    $data = \Tk\Db\Data::create();
    if (!$data->get('site.title')) {
        $data->set('site.title', 'Tk Base Project');
        $data->set('site.short.title', 'TkBase');
    }
    if (!$data->get('site.email'))
        $data->set('site.email', 'admin@example.com');

    $data->set('site.client.registration', '');
    $data->set('site.client.activation', '');

    if (!$data->get('site.meta.keywords'))
        $data->set('site.meta.keywords', '');
    if (!$data->get('site.meta.description'))
        $data->set('site.meta.description', '');
    if (!$data->get('site.global.js'))
        $data->set('site.global.js', '');
    if (!$data->get('site.global.css'))
        $data->set('site.global.css', '');

    $data->save();
} catch (\Exception $e) {
}




