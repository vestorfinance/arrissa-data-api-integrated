import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from tensorflow import keras
from tensorflow.keras import layers
import pickle

# Load data
print("Loading training data with advanced mathematical features...")
data = pd.read_csv('preprocessed_data/training-data.csv')

print(f"Dataset shape: {data.shape}")
print(f"Columns: {list(data.columns)}")

# Separate features and targets
# Exclude 'date', 'direction', and 'magnitude' from features
feature_columns = [col for col in data.columns if col not in ['date', 'direction', 'magnitude']]
print(f"\nUsing {len(feature_columns)} features for training")
print("Features:", feature_columns)

X = data[feature_columns].values
y_direction = (data['direction'] == 'UP').astype(int).values
y_magnitude = data['magnitude'].values

print(f"\nFeature matrix shape: {X.shape}")
print(f"Direction labels shape: {y_direction.shape}")
print(f"Magnitude labels shape: {y_magnitude.shape}")

# Split data
X_train, X_test, y_dir_train, y_dir_test, y_mag_train, y_mag_test = train_test_split(
    X, y_direction, y_magnitude, test_size=0.2, random_state=42, shuffle=False
)

print(f"\nTrain samples: {len(X_train)}, Test samples: {len(X_test)}")

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

# Reshape for LSTM (samples, timesteps=1, features)
X_train_lstm = X_train_scaled.reshape((X_train_scaled.shape[0], 1, X_train_scaled.shape[1]))
X_test_lstm = X_test_scaled.reshape((X_test_scaled.shape[0], 1, X_test_scaled.shape[1]))

print(f"LSTM input shape: {X_train_lstm.shape}")

# Build Direction Model
print("\n" + "="*60)
print("TRAINING DIRECTION MODEL")
print("="*60)

direction_model = keras.Sequential([
    layers.LSTM(128, activation='tanh', return_sequences=True, input_shape=(1, X_train_scaled.shape[1])),
    layers.Dropout(0.3),
    layers.LSTM(64, activation='tanh'),
    layers.Dropout(0.3),
    layers.Dense(32, activation='relu'),
    layers.Dropout(0.2),
    layers.Dense(16, activation='relu'),
    layers.Dense(1, activation='sigmoid')
])

direction_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.001),
    loss='binary_crossentropy',
    metrics=['accuracy']
)

print("\nDirection Model Architecture:")
direction_model.summary()

history_dir = direction_model.fit(
    X_train_lstm, y_dir_train,
    epochs=100,
    batch_size=32,
    validation_split=0.2,
    verbose=1
)

# Build Magnitude Model
print("\n" + "="*60)
print("TRAINING MAGNITUDE MODEL")
print("="*60)

magnitude_model = keras.Sequential([
    layers.LSTM(128, activation='tanh', return_sequences=True, input_shape=(1, X_train_scaled.shape[1])),
    layers.Dropout(0.3),
    layers.LSTM(64, activation='tanh'),
    layers.Dropout(0.3),
    layers.Dense(32, activation='relu'),
    layers.Dropout(0.2),
    layers.Dense(16, activation='relu'),
    layers.Dense(1, activation='linear')
])

magnitude_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.001),
    loss='mse',
    metrics=['mae']
)

print("\nMagnitude Model Architecture:")
magnitude_model.summary()

history_mag = magnitude_model.fit(
    X_train_lstm, y_mag_train,
    epochs=100,
    batch_size=32,
    validation_split=0.2,
    verbose=1
)

# Evaluate models
print("\n" + "="*60)
print("EVALUATION RESULTS")
print("="*60)

# Direction predictions
y_dir_pred = (direction_model.predict(X_test_lstm) > 0.5).astype(int).flatten()
direction_accuracy = np.mean(y_dir_pred == y_dir_test)

print(f"\nDirection Model:")
print(f"  Test Accuracy: {direction_accuracy*100:.2f}%")
print(f"  Baseline (53.23%): {'IMPROVED' if direction_accuracy > 0.5323 else 'WORSE'}")

# Magnitude predictions
y_mag_pred = magnitude_model.predict(X_test_lstm).flatten()
mae = np.mean(np.abs(y_mag_test - y_mag_pred))
mse = np.mean((y_mag_test - y_mag_pred)**2)
rmse = np.sqrt(mse)

# R² score
ss_res = np.sum((y_mag_test - y_mag_pred)**2)
ss_tot = np.sum((y_mag_test - np.mean(y_mag_test))**2)
r2 = 1 - (ss_res / ss_tot)

print(f"\nMagnitude Model:")
print(f"  MAE: {mae:.4f}%")
print(f"  RMSE: {rmse:.4f}%")
print(f"  R² Score: {r2:.4f}")
print(f"  Baseline MAE (0.2116%): {'IMPROVED' if mae < 0.2116 else 'WORSE'}")

# Distribution analysis
print(f"\nPrediction Distribution:")
print(f"  Predicted UP: {np.sum(y_dir_pred == 1)} ({np.sum(y_dir_pred == 1)/len(y_dir_pred)*100:.1f}%)")
print(f"  Predicted DOWN: {np.sum(y_dir_pred == 0)} ({np.sum(y_dir_pred == 0)/len(y_dir_pred)*100:.1f}%)")
print(f"  Actual UP: {np.sum(y_dir_test == 1)} ({np.sum(y_dir_test == 1)/len(y_dir_test)*100:.1f}%)")
print(f"  Actual DOWN: {np.sum(y_dir_test == 0)} ({np.sum(y_dir_test == 0)/len(y_dir_test)*100:.1f}%)")

# Save models
print("\n" + "="*60)
print("SAVING MODELS")
print("="*60)

direction_model.save('lstm_direction_advanced.keras')
magnitude_model.save('lstm_magnitude_advanced.keras')
with open('lstm_scaler_advanced.pkl', 'wb') as f:
    pickle.dump(scaler, f)

print("\nModels saved:")
print("  - lstm_direction_advanced.keras")
print("  - lstm_magnitude_advanced.keras")
print("  - lstm_scaler_advanced.pkl")

# Feature importance approximation (using model weights)
print("\n" + "="*60)
print("TOP 10 MOST IMPORTANT FEATURES (by first layer weights)")
print("="*60)

first_layer_weights = direction_model.layers[0].get_weights()[0]  # (features, lstm_units)
feature_importance = np.abs(first_layer_weights).mean(axis=1)
importance_df = pd.DataFrame({
    'feature': feature_columns,
    'importance': feature_importance
}).sort_values('importance', ascending=False)

print(importance_df.head(10).to_string(index=False))

print("\n" + "="*60)
print(f"FINAL RESULT: {direction_accuracy*100:.2f}% accuracy")
print(f"vs Baseline: 53.23% (single feature)")
print("="*60)
