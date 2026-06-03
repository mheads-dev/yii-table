<?php

declare(strict_types=1);

namespace Mheads\Yii\Table\Tests\Stubs\ActiveRecord;

use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

final class Product extends ActiveRecord
{
	public int $id;
	public string $name;
	public int $category_id;

	public function tableName(): string
	{
		return 'ar_product';
	}

	public function relationQuery(string $name): ActiveQueryInterface
	{
		return match ($name)
		{
			'category' => $this->getCategoryQuery(),
			default    => parent::relationQuery($name),
		};
	}

	public function getCategoryQuery(): ActiveQuery
	{
		return $this->hasOne(Category::class, ['id' => 'category_id']);
	}
}
