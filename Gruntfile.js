/* jshint node:true */
module.exports = function(grunt) {
	var SOURCE_DIR = '',
		BUILD_DIR = 'build/',

		BPBU_EXCLUDED_MISC = [
			'!**/bin/**',
			'!**/build/**',
			'!**/coverage/**',
			'!**/node_modules/**',
			'!**/tests/**',
			'!Gruntfile.js*',
			'!package.json*',
			'!phpcs.xml*',
			'!phpunit.xml*',
			'!.{editorconfig,gitignore,jshintrc,travis.yml,DS_Store}'
		];

	// Load tasks.
	require('matchdep').filterDev('grunt-*').forEach( grunt.loadNpmTasks );

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		checktextdomain: {
			options: {
				text_domain: 'bp-block-users',
				correct_domain: false,
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: [ '**/*.php' ].concat( BPBU_EXCLUDED_MISC ),
				expand: true
			}
		},
		clean: {
			all: [ BUILD_DIR ],
			dynamic: {
				cwd: BUILD_DIR,
				dot: true,
				expand: true,
				src: []
			}
		},
		copy: {
			files: {
				files: [
					{
						cwd: '',
						dest: 'build/',
						dot: true,
						expand: true,
						src: ['**', '!**/.{svn,git}/**'].concat( BPBU_EXCLUDED_MISC )
					}
				]
			}
		},
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: ['Gruntfile.js']
		},
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'bp-block-users.php',
					exclude: ['build/.*'],
					potComments: 'Copyright (C) 2015-<%= grunt.template.today("UTC:yyyy") %> Brandon Allen\nThis file is distributed under the same license as the BP Block Users package.',
					potFilename: 'bp-block-users.pot',
					potHeaders: {
						poedit: true,
						'report-msgid-bugs-to': 'https://github.com/thebrandonallen/bp-block-users/issues',
						'last-translator': 'BRANDON ALLEN <plugins@brandonallen.me>',
						'language-team': 'ENGLISH <plugins@brandonallen.me>'
					},
					processPot: function( pot ) {
						var translation, // Exclude meta data from pot.
							excluded_meta = [
								'Plugin Name of the plugin/theme',
								'Plugin URI of the plugin/theme',
								'Author of the plugin/theme',
								'Author URI of the plugin/theme'
								];
									for ( translation in pot.translations[''] ) {
										if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
											if ( excluded_meta.indexOf( pot.translations[''][ translation ].comments.extracted ) >= 0 ) {
												console.log( 'Excluded meta: ' + pot.translations[''][ translation ].comments.extracted );
													delete pot.translations[''][ translation ];
												}
											}
										}
						return pot;
					},
					type: 'wp-plugin'
				}
			}
		},
		phpunit: {
			'default': {
				cmd: 'phpunit',
				args: ['-c', 'phpunit.xml.dist']
			},
			'codecoverage': {
				cmd: 'phpunit',
				args: ['-c', 'phpunit.xml.dist', '--coverage-clover=coverage.clover' ]
			}
		},
		'string-replace': {
			dev: {
				files: {
					'bp-block-users.php': 'bp-block-users.php',
					'classes/class-bpbu-component.php': 'classes/class-bpbu-component.php'
				},
				options: {
					replacements: [{
						pattern: /(const\sVERSION.*)'(.*)';/gm, // For plugin version variable
						replacement: '$1\'<%= pkg.version %>\';'
					},
					{
						pattern: /(\* Version:\s*)(.*)$/gm, // For plugin header
						replacement: '$1<%= pkg.version %>'
					}]
				}
			},
			build: {
				files: {
					'bp-block-users.php': 'bp-block-users.php',
					'classes/class-bpbu-component.php': 'classes/class-bpbu-component.php',
					'readme.txt': 'readme.txt'
				},
				options: {
					replacements: [{
						pattern: /\{\{release_date\}\}/gm, // For plugin version variable
						replacement: '<%= grunt.template.today("UTC:mmmm d, yyyy") %>'
					},
					{
						pattern: /(Copyright \(C\) 2015\-)[0-9]{4}(.*)/gm,
						replacement: '$1<%= grunt.template.today("UTC:yyyy") %>$2'
					},
					{
						pattern: /(const\sVERSION.*)'(.*)';/gm, // For plugin version variable
						replacement: '$1\'<%= pkg.version %>\';'
					},
					{
						pattern: /(\* Version:\s*)(.*)$/gm, // For plugin header
						replacement: '$1<%= pkg.version %>'
					},
					{
						pattern: /(Stable tag:[\*\ ]*)(.*\S)/gim, // For readme.*
						replacement: '$1<%= pkg.version %>'
					},
					{
						pattern: /(\*\sRelease\sdate:\s)TBD$/gm,
						replacement: '$1<%= grunt.template.today("yyyy-mm-dd") %>'
					}]
				}
			}
		},
		watch: {
			js: {
				files: ['Gruntfile.js'],
				tasks: ['jshint']
			}
		},
		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			}
		}
	});

	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );

	// Build tasks.
	grunt.registerTask( 'build', [ 'clean:all', 'checktextdomain', 'string-replace:build', 'readme', 'makepot', 'copy:files' ] );

	// PHPUnit test task.
	grunt.registerMultiTask( 'phpunit', 'Runs PHPUnit tests, including the multisite tests.', function() {
		grunt.util.spawn( {
			cmd: this.data.cmd,
			args: this.data.args,
			opts: { stdio: 'inherit' }
		}, this.async() );
	} );

	// Travis CI Tasks.
	grunt.registerTask( 'travis:phpunit', ['phpunit:default'] );
	grunt.registerTask( 'travis:codecoverage', 'Runs PHPUnit tasks with code-coverage generation.', ['phpunit:codecoverage'] );

	// Register the default tasks.
	grunt.registerTask('default', ['watch']);

};
