<?php
/**
 * Register Blog object type and queries.
 *
 * @package WPGraphQL\Extensions\BuddyPress\Type\Object
 * @since 0.0.1-alpha
 */

namespace WPGraphQL\Extensions\BuddyPress\Type\Object;

use WPGraphQL\AppContext;
use WPGraphQL\Extensions\BuddyPress\Data\BlogHelper;
use WPGraphQL\Extensions\BuddyPress\Data\Factory;
use WPGraphQL\Extensions\BuddyPress\Model\Blog;

/**
 * BlogType Class.
 */
class BlogType {

	/**
	 * Name of the type.
	 *
	 * @var string Type name.
	 */
	public static $type_name = 'Blog';

	/**
	 * Register the blog type and queries to the WPGraphQL schema.
	 */
	public static function register() {
		register_graphql_object_type(
			self::$type_name,
			[
				'description'       => __( 'Info about a BuddyPress Blog.', 'wp-graphql-buddypress' ),
				'interfaces'        => [ 'Node', 'DatabaseIdentifier', 'UniformResourceIdentifiable' ],
				'fields'            => [
					'admin' => [
						'type'        => 'User',
						'description' => __( 'The admin of the blog.', 'wp-graphql-buddypress' ),
						'resolve'     => function( Blog $blog, array $args, AppContext $context ) {
							return ! empty( $blog->admin )
								? $context->get_loader( 'user' )->load_deferred( $blog->admin )
								: null;
						},
					],
					'name' => [
						'type'        => 'String',
						'description' => __( 'The name of the Blog.', 'wp-graphql-buddypress' ),
					],
					'description' => [
						'type'        => 'String',
						'description' => __( 'The description of the blog.', 'wp-graphql-buddypress' ),
						'args'        => [
							'format' => [
								'type'        => 'ContentFieldFormatEnum',
								'description' => __( 'Format of the field output.', 'wp-graphql-buddypress' ),
							],
						],
						'resolve'     => function( Blog $blog, array $args ) {
							if ( empty( $blog->description ) ) {
								return null;
							}

							if ( isset( $args['format'] ) && 'raw' === $args['format'] ) {
								return $blog->description;
							}

							return stripslashes( $blog->description );
						},
					],
					'path' => [
						'type'        => 'String',
						'description' => __( 'The path of the blog.', 'wp-graphql-buddypress' ),
					],
					'domain' => [
						'type'        => 'String',
						'description' => __( 'The domain of the blog.', 'wp-graphql-buddypress' ),
					],
					'lastActivity' => [
						'type'        => 'String',
						'description' => __( 'The last activity date from the blog, in the site\'s timezone.', 'wp-graphql-buddypress' ),
					],
					'attachmentAvatar' => [
						'type'        => 'Attachment',
						'description' => __( 'Attachment Avatar of the blog.', 'wp-graphql-buddypress' ),
						'resolve'     => function ( Blog $blog ) {

							// Bail early, if disabled.
							if ( false === buddypress()->avatar->show_avatars ) {
								return null;
							}

							return Factory::resolve_attachment( $blog->databaseId ?? 0, 'blog' );
						},
					],
					'attachmentCover' => [
						'type'        => 'Attachment',
						'description' => __( 'Attachment Cover of the blog.', 'wp-graphql-buddypress' ),
						'resolve'     => function ( Blog $blog ) {
							return Factory::resolve_attachment_cover( $blog->databaseId ?? 0, 'blogs' );
						},
					],
				],
				'resolve_node'      => function( $node, $id, string $type, AppContext $context ) {
					if ( self::$type_name === $type ) {
						$node = Factory::resolve_blog_object( $id, $context );
					}

					return $node;
				},
				'resolve_node_type' => function( $type, $node ) {
					if ( $node instanceof Blog ) {
						$type = self::$type_name;
					}

					return $type;
				},
			]
		);

		register_graphql_field(
			'RootQuery',
			'blogBy',
			[
				'type'        => self::$type_name,
				'description' => __( 'Get a BuddyPress Blog object.', 'wp-graphql-buddypress' ),
				'args'        => [
					'id'           => [
						'type'        => 'ID',
						'description' => __( 'Get the object by its global ID.', 'wp-graphql-buddypress' ),
					],
					'blogId'      => [
						'type'        => 'Int',
						'description' => __( 'Get the object by its database ID.', 'wp-graphql-buddypress' ),
					],
				],
				'resolve'     => function ( $source, array $args, AppContext $context ) {
					$blog = BlogHelper::get_blog_from_input( $args );
					return Factory::resolve_blog_object( $blog->blog_id, $context );
				},
			]
		);
	}
}
