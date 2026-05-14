<?php

if (!function_exists('lt_torrent_metadata_service_values')) {
    function lt_torrent_metadata_service_values($torrent, $schema)
    {
        return lt_torrent_metadata_values_from_row($torrent, $schema);
    }
}
