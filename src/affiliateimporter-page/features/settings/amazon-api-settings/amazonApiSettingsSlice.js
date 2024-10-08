import { createSlice } from '@reduxjs/toolkit';
import { fetchAmazonApiSettings, saveAmazonApiSettings, verifyAmazonApiSettings } from '../../../services/apiService';

const initialState = {
	amazonAccessKey: '',
	amazonSecretKey: '',
	amazonCountryCode: 'us',
	amazonAffiliateId: '',

	isSettingsLoading: false,
	isAmazonAPISettingsSaving: false,
	isAmazonApiSettingsVerifying: false,

	error: null,
	message: '',
	settingsToastMessage: ''
}

export const amazonApiSettingsSlice = createSlice({
	name: 'amazonApiSettings',
	initialState,
	reducers: {
		setAmazonAccessKey: (state, action) => {
			state.amazonAccessKey = action.payload;
		},
		setAmazonSecretKey: (state, action) => {
			state.amazonSecretKey = action.payload;
		},
		setAmazonCountryCode: (state, action) => {
			state.amazonCountryCode = action.payload;
		},
		setAmazonAffiliateId: (state, action) => {
			state.amazonAffiliateId = action.payload;
		},
		setSettingsToastMessage: (state, action) => {
			state.settingsToastMessage = action.payload;
		},
	},
	extraReducers: (builder) => {
		builder.addCase(fetchAmazonApiSettings.pending, (state) => {
			state.isSettingsLoading = true;
		}),
		builder.addCase(fetchAmazonApiSettings.fulfilled, (state, action) => {
			state.isSettingsLoading = false;
			state.error = null;
			state.amazonAccessKey = action.payload.access_key;
			state.amazonSecretKey = action.payload.secret_key;
			state.amazonCountryCode = action.payload.country_code;
			state.amazonAffiliateId = action.payload.affiliate_id;
		}),
		builder.addCase(fetchAmazonApiSettings.rejected, (state, action) => {
			state.isSettingsLoading = false;
			state.error = (action.error?.message) ? action.error.message : null;
		}),
		builder.addCase(saveAmazonApiSettings.pending, (state) => {
			state.isAmazonAPISettingsSaving = true;
		}),
		builder.addCase(saveAmazonApiSettings.fulfilled, (state, action) => {
			state.isAmazonAPISettingsSaving = false;
			state.error = null;
			state.settingsToastMessage = 'All the settings have been verified and saved successfully';
		}),
		builder.addCase(saveAmazonApiSettings.rejected, (state, action) => {
			state.isAmazonAPISettingsSaving = false;
			state.error = (action.error?.message) ? action.error.message : null;
		}),
		builder.addCase(verifyAmazonApiSettings.pending, (state) => {
			state.isAmazonApiSettingsVerifying = true;
		}),
		builder.addCase(verifyAmazonApiSettings.fulfilled, (state, action) => {
			state.isAmazonApiSettingsVerifying = false;
			state.error = null;
		}),
		builder.addCase(verifyAmazonApiSettings.rejected, (state, action) => {
			state.isAmazonApiSettingsVerifying = false;
			state.error = (action.payload?.message) ? action.payload.message : 'Amazon API settings are not valid';
		})
	}
})

export const { setSettingsActiveTab, setAmazonAccessKey, setAmazonSecretKey, setAmazonCountryCode, setAmazonAffiliateId, setSettingsToastMessage } = amazonApiSettingsSlice.actions
export default amazonApiSettingsSlice.reducer;