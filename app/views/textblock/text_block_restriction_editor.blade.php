<?php

foreach($restrictions as $restriction) {
    printf("id: %d category: %s<br/>\n", $restriction->id, $restriction->category);
}
