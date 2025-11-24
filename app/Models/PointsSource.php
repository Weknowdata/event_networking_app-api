<?php

namespace App\Models;

enum PointsSource: string
{
    case CONNECTION = 'connection';
    case CONNECTION_NOTE = 'connection_note';
}
