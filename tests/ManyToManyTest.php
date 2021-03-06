<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;

class ManyToMany extends AbstractTestCase
{
    public function testAddingRelatedWithoutRelationshipModel()
    {
        $input = [
            'name' => 'São Paulo',
            'cities' => [
                ['city_id' => City::whereName('São Paulo')->first()->id],
                ['city_id' => City::whereName('Taboão da Serra')->first()->id],
            ],
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::find($region->id);
        $this->assertEquals(2, count($region->cities));
    }

    public function testAddingObjectsRelatedWithoutRelationshipModel()
    {
        $data = ['name' => 'Ribeirão'];
        $city = new City;
        $this->assertTrue($city->saveAll($data));
        $city = City::find($city->id);

        $data = ['name' => 'Cravinhos'];
        $city2 = new City;
        $this->assertTrue($city2->saveAll($data));
        $city2 = City::find($city2->id);

        $input = [
            'name' => 'region_x',
            'cities' => [
                $city->toArray(),
                $city2->toArray(),
                ['name' => 'Bonfim'],
            ]
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::find($region->id);
        $this->assertEquals(3, count($region->cities));
    }

    public function testRemoveRelated()
    {
        $input = [
            'name' => 'São Paulo',
            'cities' => [
                ['city_id' => City::whereName('São Paulo')->first()->id],
                ['city_id' => City::whereName('Taboão da Serra')->first()->id],
            ],
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::find($region->id);
        $this->assertEquals(2, count($region->cities));

        $this->assertTrue($region->saveAll(['cities' => [
            '_delete' => true, 'city_id' => City::whereName('São Paulo')->first()->id]
        ]));

        $region = Region::find($region->id);
        $this->assertEquals(1, count($region->cities));
    }

    public function testAddAndCreateRelatedWithoutRelationshipModel()
    {
        $input = [
            'name' => 'São Paulo',
            'cities' => [
                ['name' => 'Test city', '_create' => true],
            ],
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::find($region->id);
        $this->assertEquals(1, count($region->cities));
    }

    public function testShouldNotSaveAllWithErrors()
    {
        $input = ['name' => 'Barrinha'];
        $region = new Region;
        $this->assertTrue($region->saveAll($input));
        $this->assertFalse($region->saveAll(['name' => '']));

        $region = Region::find($region->id);
        $this->assertEquals($input['name'], $region->name);
    }

    public function testShouldSaveWithRelationshipModel()
    {
        $post = Post::find(1);
        $input = $post->toArray();
        $input['authors'] = [
            ['user_id' => 1, 'main' => 1],
            ['user_id' => 2, 'main' => 0],
        ];

        $this->assertTrue($post->saveAll($input));

        $post = Post::find(1);
        $this->assertEquals(2, count($post->authors));
    }

    public function testShouldUseSync()
    {
        $input = [
            'title' => 'Post x',
            'content' => 'Content x',
            'authors' => [
                'user_id' => [1, 2],
            ]
        ];

        $post = new Post;
        $this->assertTrue($post->createAll($input));
        $this->assertEquals(2, $post->authors->count());
    }

    public function testShoulSaveBelongsToManyWithRelationshipModel()
    {
        $data = [
            'name' => 'area_x',
            'cities' => [
                'city_id' => 1,
            ]
        ];

        $area = new Area;
        $this->assertTrue($area->saveAll($data));
        $area = Area::with('cities')->find(1);
        $this->assertEquals(1, $area->cities->first()->id);

        define('xpto', true);
        $this->assertTrue($area->saveAll([
            'id' => 1,
            'name' => 'area_y',
            'cities' => [
                'id' => $area->cities->first()->pivot->id,
                'city_id' => 2,
                'area_id' => 1,
            ]
        ]));
        $area = Area::with('cities')->find(1);
        $this->assertEquals(2, $area->cities->first()->id);
    }

    public function testShoulSaveBelongsToManyIfRelatedObjectIsProvided()
    {
        $data = ['name' => 'Ribeirão'];
        $city = new City;
        $this->assertTrue($city->saveAll($data));
        $city = City::find($city->id);

        $data = ['name' => 'Cravinhos'];
        $city2 = new City;
        $this->assertTrue($city2->saveAll($data));
        $city2 = City::find($city2->id);

        $data = [
            'name' => 'area_x',
            'cities' => [
                $city->toArray(),
                $city2->toArray(),
            ]
        ];

        $area = new Area;
        $this->assertTrue($area->saveAll($data));
        $area = Area::with('cities')->find($area->id);
        $this->assertEquals($area->cities->first()->name, 'Ribeirão');

        $data = ['name' => 'Sertaozinho'];
        $city = new City;
        $this->assertTrue($city->saveAll($data));
        $city = City::find($city->id);

        $data = [
            'name' => 'area_x_v2',
            'cities' => [
                $city->toArray(),
                $city2->toArray(),
            ]
        ];

        $this->assertTrue($area->saveAll($data));
        $area = Area::with('cities')->find($area->id);
//        dd($area->toArray());
        $this->assertEquals($area->cities->first()->name, 'Sertaozinho');
        $this->assertEquals(2, count($area->cities));

    }
}
