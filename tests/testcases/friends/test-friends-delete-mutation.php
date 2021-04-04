<?php

/**
 * Test_Friendship_Delete_Mutation Class.
 *
 * @group friends
 */
class Test_Friendship_Delete_Mutation extends WPGraphQL_BuddyPress_UnitTestCase {

	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
	}

	public function test_initiator_withdraw_friendship() {
		$u1 = $this->bp_factory->user->create();
		$u2 = $this->bp_factory->user->create();

		$this->create_friendship_object( $u1, $u2 );

		$this->bp->set_current_user( $u1 );

		$this->assertEquals(
			[
				'data' => [
					'deleteFriendship' => [
						'clientMutationId' => $this->client_mutation_id,
						'deleted' => true,
						'friendship' => [
							'initiator' => [
								'userId' => $u1,
							],
							'friend' => [
								'userId' => $u2,
							],
						],
					],
				],
			],
			$this->delete_friendship( $u1, $u2 )
		);
	}

	public function test_friend_reject_friendship() {
		$u1 = $this->bp_factory->user->create();
		$u2 = $this->bp_factory->user->create();

		$this->create_friendship_object( $u1, $u2 );

		$this->bp->set_current_user( $u2 );

		$this->assertEquals(
			[
				'data' => [
					'deleteFriendship' => [
						'clientMutationId' => $this->client_mutation_id,
						'deleted' => true,
						'friendship' => [
							'initiator' => [
								'userId' => $u1,
							],
							'friend' => [
								'userId' => $u2,
							],
						],
					],
				],
			],
			$this->delete_friendship( $u1, $u2 )
		);
	}

	public function test_delete_friendship_with_user_not_logged_in() {
		$u1 = $this->bp_factory->user->create();
		$u2 = $this->bp_factory->user->create();

		$this->create_friendship_object( $u1, $u2 );

		$this->assertQueryFailed( $this->delete_friendship( $u1, $u2 ) )
			->expectedErrorMessage( 'Sorry, you do not have permission to perform this action.' );
	}

	public function test_user_can_not_delete_or_reject_other_user_friendship_request() {
		$u1 = $this->bp_factory->user->create();
		$u2 = $this->bp_factory->user->create();
		$u3 = $this->bp_factory->user->create();

		$this->create_friendship_object( $u1, $u2 );

		$this->bp->set_current_user( $u3 );

		$this->assertQueryFailed( $this->delete_friendship( $u1, $u2 ) )
			->expectedErrorMessage( 'Sorry, you do not have permission to perform this action.' );
	}

	public function test_delete_with_invalid_users() {
		$u1 = $this->bp_factory->user->create();
		$u2 = $this->bp_factory->user->create();

		$this->create_friendship_object( $u1, $u2 );

		$this->bp->set_current_user( $u1 );

		// Invalid friend.
		$this->assertQueryFailed( $this->delete_friendship( $u1, 111 ) )
			->expectedErrorMessage( 'There was a problem confirming if user is valid.' );

		$this->bp->set_current_user( $u2 );

		// Invalid initiator.
		$this->assertQueryFailed( $this->delete_friendship( 111, $u2 ) )
			->expectedErrorMessage( 'There was a problem confirming if user is valid.' );
	}

	protected function create_friendship_object( $u = 0, $a = 0 ) {
		if ( empty( $u ) ) {
			$u = $this->factory->user->create();
		}

		if ( empty( $a ) ) {
			$a = $this->factory->user->create();
		}

		$friendship                    = new BP_Friends_Friendship();
		$friendship->initiator_user_id = $u;
		$friendship->friend_user_id    = $a;
		$friendship->is_confirmed      = 0;
		$friendship->date_created      = bp_core_current_time();
		$friendship->save();

		return $friendship->id;
	}
}