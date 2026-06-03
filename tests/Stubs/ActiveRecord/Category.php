<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Stubs\ActiveRecord;

use Yiisoft\ActiveRecord\ActiveRecord;

final class Category extends ActiveRecord
{
	public int $id;
	public string $title;

	public function tableName(): string
	{
		return 'ar_category';
	}
}
