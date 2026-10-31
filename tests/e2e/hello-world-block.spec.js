/**
 * Runs once chore/testing-ci adds Playwright + axe on wp-env.
 */
// chore/testing-ci adds these devDependencies; drop this line then.
/* eslint-disable import/no-extraneous-dependencies */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const AxeBuilder = require('@axe-core/playwright').default;

test.describe('blammo/hello-world', () => {
	test('inserts, publishes and renders on the front end', async ({
		admin,
		editor,
		page,
	}) => {
		await admin.createNewPost({ title: 'Hello World block' });
		await editor.insertBlock({
			name: 'blammo/hello-world',
			attributes: { name: 'Playwright' },
		});

		// Exactly one wrapper in the editor: a nested preview would double it.
		await expect(editor.canvas.locator('.hello-world-block')).toHaveCount(
			1
		);
		await expect(
			editor.canvas.locator('.hello-world-block__greeting')
		).toHaveText('Hello, Playwright!');

		const postId = await editor.publishPost();
		await page.goto(`/?p=${postId}`);

		await expect(page.locator('.hello-world-block__greeting')).toHaveText(
			'Hello, Playwright!'
		);
	});

	test('front end passes axe @a11y', async ({ admin, editor, page }) => {
		await admin.createNewPost({ title: 'Hello World a11y' });
		await editor.insertBlock({ name: 'blammo/hello-world' });
		const postId = await editor.publishPost();
		await page.goto(`/?p=${postId}`);

		const results = await new AxeBuilder({ page })
			.withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
			.analyze();
		expect(results.violations).toEqual([]);
	});
});
