<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\I18n;

final class TableMessage
{
	public const string NUMBER_EXACTLY = 'number_filter.exactly';
	public const string NUMBER_MORE_THAN = 'number_filter.more_than';
	public const string NUMBER_LESS_THAN = 'number_filter.less_than';
	public const string NUMBER_RANGE = 'number_filter.range';

	public const string DATE_EXACT = 'date_filter.exact';
	public const string DATE_RANGE = 'date_filter.range';
	public const string DATE_TODAY = 'date_filter.today';
	public const string DATE_YESTERDAY = 'date_filter.yesterday';
	public const string DATE_TOMORROW = 'date_filter.tomorrow';
	public const string DATE_CURRENT_WEEK = 'date_filter.current_week';
	public const string DATE_PREVIOUS_WEEK = 'date_filter.previous_week';
	public const string DATE_NEXT_WEEK = 'date_filter.next_week';
}
