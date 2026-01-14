import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score, mean_absolute_error, r2_score
import tensorflow as tf
from tensorflow import keras
from tensorflow.keras import layers
import pickle

print("TensorFlow version:", tf.__version__)
print("\nLoading DXY training data...")
df = pd.read_csv('preprocessed_data/training-data-dxy.csv')

# Filter data from 2023 to 2024 only
df['date'] = pd.to_datetime(df['date'])
df = df[(df['date'] >= '2023-01-01') & (df['date'] <= '2024-12-31')]
df = df.reset_index(drop=True)

print(f"Total records (2023-2024): {len(df)}")
print(f"Date range: {df['date'].min()} to {df['date'].max()}")
print(f"\nFirst few rows:")
print(df.head())

# Prepare features (X) - ONLY next week state of economy
X = df[['next_week_state_of_the_economy']].values

print(f"\n*** USING ONLY NEXT WEEK STATE OF ECONOMY ***")
print(f"Features: next_week_state_of_the_economy")

# Prepare targets (y)
df['direction_binary'] = df['direction'].apply(lambda x: 1 if x == 'UP' else 0)
y_direction = df['direction_binary'].values
y_magnitude = df['magnitude'].values

print(f"\nFeatures shape: {X.shape}")
print(f"Direction labels shape: {y_direction.shape}")
print(f"Magnitude labels shape: {y_magnitude.shape}")

# Check class distribution
print(f"\nDirection distribution:")
print(f"UP: {np.sum(y_direction == 1)} ({np.sum(y_direction == 1)/len(y_direction)*100:.2f}%)")
print(f"DOWN: {np.sum(y_direction == 0)} ({np.sum(y_direction == 0)/len(y_direction)*100:.2f}%)")

# ============================================================
# CREATE SEQUENCES WITH LOOKBACK WINDOW
# ============================================================
print("\n" + "="*60)
print("CREATING TEMPORAL SEQUENCES")
print("="*60)

LOOKBACK_DAYS = 10  # Look at past 10 days of economic states

def create_sequences(X, y_dir, y_mag, lookback):
    """Create sequences with lookback window for temporal learning"""
    X_seq, y_dir_seq, y_mag_seq = [], [], []
    
    for i in range(lookback, len(X)):
        # Take past 'lookback' days as input sequence
        X_seq.append(X[i-lookback:i])
        y_dir_seq.append(y_dir[i])
        y_mag_seq.append(y_mag[i])
    
    return np.array(X_seq), np.array(y_dir_seq), np.array(y_mag_seq)

# Scale features first
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# Create sequences
X_seq, y_dir_seq, y_mag_seq = create_sequences(X_scaled, y_direction, y_magnitude, LOOKBACK_DAYS)

print(f"\nSequence shape: {X_seq.shape}")
print(f"Each sample contains {LOOKBACK_DAYS} days of {X_seq.shape[2]} feature (next_week_state_of_the_economy)")

# Split into train/test (80/20)
split_idx = int(len(X_seq) * 0.8)
X_train = X_seq[:split_idx]
X_test = X_seq[split_idx:]
y_dir_train = y_dir_seq[:split_idx]
y_dir_test = y_dir_seq[split_idx:]
y_mag_train = y_mag_seq[:split_idx]
y_mag_test = y_mag_seq[split_idx:]

print(f"\nTraining samples: {len(X_train)}")
print(f"Test samples: {len(X_test)}")

# ============================================================
# ATTENTION MECHANISM
# ============================================================
class AttentionLayer(layers.Layer):
    """Custom attention layer to focus on important past days"""
    
    def __init__(self, units=32, **kwargs):
        super(AttentionLayer, self).__init__(**kwargs)
        self.units = units
    
    def build(self, input_shape):
        self.W = self.add_weight(
            name='attention_weight',
            shape=(input_shape[-1], self.units),
            initializer='glorot_uniform',
            trainable=True
        )
        self.b = self.add_weight(
            name='attention_bias',
            shape=(self.units,),
            initializer='zeros',
            trainable=True
        )
        self.u = self.add_weight(
            name='attention_context',
            shape=(self.units,),
            initializer='glorot_uniform',
            trainable=True
        )
    
    def call(self, inputs):
        # inputs shape: (batch, timesteps, features)
        # Calculate attention scores
        score = tf.nn.tanh(tf.tensordot(inputs, self.W, axes=1) + self.b)
        attention_weights = tf.nn.softmax(tf.tensordot(score, self.u, axes=1), axis=1)
        
        # Weighted sum
        attention_weights = tf.expand_dims(attention_weights, -1)
        weighted_input = inputs * attention_weights
        
        return tf.reduce_sum(weighted_input, axis=1)

# ============================================================
# ADVANCED DIRECTION CLASSIFIER
# ============================================================
print("\n" + "="*60)
print("TRAINING ADVANCED DIRECTION CLASSIFIER")
print("="*60)
print("\nArchitecture:")
print("- Bidirectional LSTM (learns from past AND future context)")
print("- Multi-layer LSTM (deeper temporal understanding)")
print("- Attention mechanism (focuses on important days)")
print("- Dropout & Batch Normalization (prevents overfitting)")

# Build advanced model with attention
direction_input = layers.Input(shape=(LOOKBACK_DAYS, 1))  # Changed to 1 feature

# First Bidirectional LSTM layer
x = layers.Bidirectional(layers.LSTM(128, return_sequences=True))(direction_input)
x = layers.BatchNormalization()(x)
x = layers.Dropout(0.3)(x)

# Second Bidirectional LSTM layer
x = layers.Bidirectional(layers.LSTM(64, return_sequences=True))(x)
x = layers.BatchNormalization()(x)
x = layers.Dropout(0.3)(x)

# Attention mechanism - learns which past days are most important
attention_output = AttentionLayer(units=32)(x)

# Dense layers
x = layers.Dense(64, activation='relu')(attention_output)
x = layers.BatchNormalization()(x)
x = layers.Dropout(0.2)(x)

x = layers.Dense(32, activation='relu')(x)
x = layers.Dropout(0.1)(x)

# Output
direction_output = layers.Dense(1, activation='sigmoid')(x)

direction_model = keras.Model(inputs=direction_input, outputs=direction_output, name='advanced_direction_model')

direction_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.0005),
    loss='binary_crossentropy',
    metrics=['accuracy']
)

print("\nModel Summary:")
direction_model.summary()

# Callbacks for better training
early_stopping = keras.callbacks.EarlyStopping(
    monitor='val_loss',
    patience=15,
    restore_best_weights=True,
    verbose=1
)

reduce_lr = keras.callbacks.ReduceLROnPlateau(
    monitor='val_loss',
    factor=0.5,
    patience=7,
    min_lr=0.00001,
    verbose=1
)

print("\nTraining with callbacks (Early Stopping, Learning Rate Reduction)...")
history_dir = direction_model.fit(
    X_train, y_dir_train,
    epochs=100,
    batch_size=32,
    validation_split=0.2,
    callbacks=[early_stopping, reduce_lr],
    verbose=1
)

# Predict
y_dir_pred_prob = direction_model.predict(X_test, verbose=0)
y_dir_pred = (y_dir_pred_prob > 0.5).astype(int).flatten()

# Evaluate
print("\n--- Direction Prediction Results ---")
print(f"Accuracy: {accuracy_score(y_dir_test, y_dir_pred)*100:.2f}%")
print("\nClassification Report:")
print(classification_report(y_dir_test, y_dir_pred, target_names=['DOWN', 'UP']))

# ============================================================
# ADVANCED MAGNITUDE REGRESSOR
# ============================================================
print("\n" + "="*60)
print("TRAINING ADVANCED MAGNITUDE REGRESSOR")
print("="*60)

# Build advanced magnitude model
magnitude_input = layers.Input(shape=(LOOKBACK_DAYS, 1))  # Changed to 1 feature

# Bidirectional LSTM layers
x = layers.Bidirectional(layers.LSTM(128, return_sequences=True))(magnitude_input)
x = layers.BatchNormalization()(x)
x = layers.Dropout(0.3)(x)

x = layers.Bidirectional(layers.LSTM(64, return_sequences=True))(x)
x = layers.BatchNormalization()(x)
x = layers.Dropout(0.3)(x)

# Attention
attention_output = AttentionLayer(units=32)(x)

# Dense layers
x = layers.Dense(64, activation='relu')(attention_output)
x = layers.BatchNormalization()(x)
x = layers.Dropout(0.2)(x)

x = layers.Dense(32, activation='relu')(x)
x = layers.Dropout(0.1)(x)

# Output
magnitude_output = layers.Dense(1, activation='linear')(x)

magnitude_model = keras.Model(inputs=magnitude_input, outputs=magnitude_output, name='advanced_magnitude_model')

magnitude_model.compile(
    optimizer=keras.optimizers.Adam(learning_rate=0.0005),
    loss='mean_absolute_error',
    metrics=['mae']
)

print("\nModel Summary:")
magnitude_model.summary()

print("\nTraining with callbacks...")
history_mag = magnitude_model.fit(
    X_train, y_mag_train,
    epochs=100,
    batch_size=32,
    validation_split=0.2,
    callbacks=[early_stopping, reduce_lr],
    verbose=1
)

# Predict
y_mag_pred = magnitude_model.predict(X_test, verbose=0).flatten()

# Evaluate
print("\n--- Magnitude Prediction Results ---")
mae = mean_absolute_error(y_mag_test, y_mag_pred)
r2 = r2_score(y_mag_test, y_mag_pred)
print(f"Mean Absolute Error: {mae:.4f}%")
print(f"R² Score: {r2:.4f}")

# ============================================================
# SAVE MODELS
# ============================================================
print("\n" + "="*60)
print("SAVING MODELS")
print("="*60)

direction_model.save('lstm_advanced_direction.keras')
magnitude_model.save('lstm_advanced_magnitude.keras')

model_config = {
    'scaler': scaler,
    'lookback_days': LOOKBACK_DAYS,
    'feature_names': ['next_week_state']
}

with open('lstm_advanced_config.pkl', 'wb') as f:
    pickle.dump(model_config, f)

print("\nModels saved:")
print("  - lstm_advanced_direction.keras")
print("  - lstm_advanced_magnitude.keras")
print("  - lstm_advanced_config.pkl")

# ============================================================
# SAMPLE PREDICTIONS
# ============================================================
print("\n" + "="*60)
print("SAMPLE PREDICTIONS (First 10 from test set)")
print("="*60)
print("\n{:<6} {:<8} {:<8} {:<10} {:<12} {:<12}".format(
    "Index", "Actual", "Predicted", "Prob", "Actual Mag", "Pred Mag"
))
print("-" * 70)

for i in range(min(10, len(y_dir_test))):
    actual_dir = 'UP' if y_dir_test[i] == 1 else 'DOWN'
    pred_dir = 'UP' if y_dir_pred[i] == 1 else 'DOWN'
    prob = y_dir_pred_prob[i][0]
    actual_mag = y_mag_test[i]
    pred_mag = y_mag_pred[i]
    
    print("{:<6} {:<8} {:<8} {:<10.2f} {:<12.2f} {:<12.2f}".format(
        i, actual_dir, pred_dir, prob, actual_mag, pred_mag
    ))

# ============================================================
# ARCHITECTURE IMPROVEMENTS
# ============================================================
print("\n" + "="*60)
print("ADVANCED ARCHITECTURE FEATURES")
print("="*60)

print("\n1. TEMPORAL SEQUENCES")
print(f"   - Using {LOOKBACK_DAYS}-day lookback window")
print("   - Model sees patterns across multiple days")
print("   - Captures momentum and trends")

print("\n2. BIDIRECTIONAL LSTM")
print("   - Processes sequences forward AND backward")
print("   - Better context understanding")
print("   - 2x parameters = 2x learning capacity")

print("\n3. ATTENTION MECHANISM")
print("   - Learns which past days are most important")
print("   - Focuses on significant economic events")
print("   - Dynamic weighting of historical data")

print("\n4. DEEP ARCHITECTURE")
print("   - Multiple LSTM layers (128→64 units)")
print("   - Hierarchical feature learning")
print("   - Captures complex temporal patterns")

print("\n5. REGULARIZATION")
print("   - Dropout (30%, 20%, 10% layers)")
print("   - Batch Normalization")
print("   - Prevents overfitting, improves generalization")

print("\n6. ADAPTIVE LEARNING")
print("   - Early Stopping (patience=15)")
print("   - Learning Rate Reduction (factor=0.5)")
print("   - Optimal convergence without overtraining")

print("\n" + "="*60)
print("TRAINING COMPLETE!")
print("="*60)

print("\nThis advanced model has:")
print(f"- {direction_model.count_params():,} parameters in direction model")
print(f"- {magnitude_model.count_params():,} parameters in magnitude model")
print("- Temporal memory across", LOOKBACK_DAYS, "days")
print("- Attention-based importance weighting")
print("- Bidirectional context processing")
