const { registerBlockType } = wp.blocks;
const { SelectControl } = wp.components;
const { withSelect } = wp.data;

registerBlockType('saas/profile-embed', {
    title: 'SaaS Profile Embed',
    icon: 'id-alt',
    category: 'widgets',
    attributes: {
        profile_id: { type: 'number', default: 0 }
    },
    edit: withSelect((select) => {
        return {
            profiles: select('core').getEntityRecords('postType', 'saas_profile')
        };
    })(({ profiles, attributes, setAttributes }) => {
        if (!profiles) return 'Loading Profiles...';
        if (profiles.length === 0) return 'No profiles found.';

        const options = profiles.map(p => ({ label: p.title.rendered, value: p.id }));

        return (
            <div style={{ padding: '20px', border: '1px solid #7551FF', borderRadius: '8px' }}>
                <h4>SaaS Profile Embed</h4>
                <SelectControl
                    label="Select Profile"
                    value={attributes.profile_id}
                    options={[{ label: 'Select a profile...', value: 0 }, ...options]}
                    onChange={(val) => setAttributes({ profile_id: parseInt(val) })}
                />
            </div>
        );
    }),
    save: () => null // Handled by PHP render_callback
});
