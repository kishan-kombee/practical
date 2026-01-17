<?php

namespace Database\Factories;

use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsTemplateFactory extends Factory
{
    protected $model = SmsTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => $this->faker->text(50),
            'label' => $this->faker->text(50),
            'message' => $this->faker->paragraph(),
            'dlt_message_id' => $this->faker->text(100),
            'status' => 'Y',
        ];
    }
}
