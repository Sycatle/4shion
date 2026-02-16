<?php

use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('shows pending reviews by default', function () {
    $pending = Review::factory()->pending()->create();
    $published = Review::factory()->create();

    Livewire::test(ManageReviews::class)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$published]);
});

it('publishes a review', function () {
    $review = Review::factory()->pending()->create();

    Livewire::test(ManageReviews::class)
        ->callAction(TestAction::make('approve')->table($review));

    expect($review->fresh()->approved_at)->not->toBeNull();
});

it('unpublishes a review', function () {
    $review = Review::factory()->create();

    Livewire::test(ManageReviews::class)
        ->removeTableFilter('approved')
        ->callAction(TestAction::make('unpublish')->table($review));

    expect($review->fresh()->approved_at)->toBeNull();
});
