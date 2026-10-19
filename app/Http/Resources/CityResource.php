<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'id_county' => $this->id_county,
            'county' => $this->whenLoaded('county', fn () => $this->county ? [
                'id' => $this->county->id,
                'name' => $this->county->name,
            ] : null),
        ];
    }
}
