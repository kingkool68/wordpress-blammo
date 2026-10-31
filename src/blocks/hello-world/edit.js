/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Edit component for blammo/hello-world.
 *
 * @param {Object}                  props               Block props.
 * @param {Object}                  props.attributes    Block attributes.
 * @param {(attrs: Object) => void} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Greeting', 'blammo')}>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Who to greet', 'blammo')}
						help={__('Leave empty to greet the world.', 'blammo')}
						value={attributes.name}
						onChange={(name) => setAttributes({ name })}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<ServerSideRender
					block="blammo/hello-world"
					attributes={attributes}
				/>
			</div>
		</>
	);
}
