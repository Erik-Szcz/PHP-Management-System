<?php

//Single source of truth for card conditions
enum CardCondition: string
{
    case Mint = 'Mint';
    case NearMint = 'Near Mint';
    case Excellent = 'Excellent';
    case Good = 'Good';
    case LightPlayed = 'Light Played';
    case Played = 'Played';
    case Poor = 'Poor';
}
