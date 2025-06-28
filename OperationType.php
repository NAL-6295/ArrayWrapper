<?php

declare(strict_types=1);

namespace NAL_6295\Collections;

enum OperationType: int
{
	case WHERE = 0;
	case SELECT = 1;
	case REDUCE = 2;
	case GROUP_BY = 3;
	case JOIN = 4;
	case ORDER_BY = 5;	
	case ZIP = 6;
}

?>