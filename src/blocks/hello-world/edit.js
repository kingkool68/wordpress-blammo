/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

/**
 * Edit component for blammo/hello-world.
 *
 * The preview mirrors render.php in JS. ServerSideRender would nest a second
 * wrapper (with every support style applied again) inside useBlockProps().
 *
 * @param {Object}                  props               Block props.
 * @param {Object}                  props.attributes    Block attributes.
 * @param {(attrs: Object) => void} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps({ className: 'hello-world-block' });
	const name = attributes.name.trim() || __('world', 'blammo');

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Greeting', 'blammo')}>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Who to greet', 'blammo')}
						help={__('Leave empty to greet the world.', 'blammo')}
						placeholder={__('world', 'blammo')}
						value={attributes.name}
						onChange={(value) => setAttributes({ name: value })}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<p className="hello-world-block__greeting">
					{sprintf(
						/* translators: %s: who to greet, e.g. "world". */
						__('Hello, %s!', 'blammo'),
						name
					)}
				</p>
			</div>
		</>
	);
}
