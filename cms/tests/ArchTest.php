<?php

arch()
    ->expect('cms')
    ->not->toUse(['die', 'dd', 'dump']);
